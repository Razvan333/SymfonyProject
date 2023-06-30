<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Repository\CustomerRepository;
use App\Service\CustomerControllerService;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private Client $cache;
    private EntityManagerInterface $entityManager;
    private CustomerRepository $customerRepository;
    private CustomerControllerService $customerService;
    private const CACHE_TIMER = 86400;
    private const ALL_CUSTOMERS_CACHE_KEY = 'all_customers';
    private const CUSTOMER_CACHE_KEY = 'customer_';

    public function __construct (
        ValidatorInterface $validator,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager,
        Client $cache,
        CustomerControllerService $customerService
    ) {
        $this->validator = $validator;
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
        $this->customerService = $customerService;

        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, string $format, array $context): string {
                return $object->getId();
            },
        ];
        $normalizer = new ObjectNormalizer(
            null,
            null,
            null,
            null,
            null,
            null,
            $defaultContext
        );

        $this->serializer = new Serializer([$normalizer], [$encoder]);
    }

    #[Route('/customer', name: 'get_all_customers', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        $cacheKey = self::ALL_CUSTOMERS_CACHE_KEY . '_page_' . $page . '_per_page_' . $perPage;

        $customers = $this->cache->get($cacheKey);

        if (null === $customers) {
            $repository = $this->entityManager->getRepository(Customer::class);

            $totalCustomers = $repository->count([]);

            $offset = ($page - 1) * $perPage;
            $customers = $repository->findBy([], null, $perPage, $offset);

            if (null == $customers) {
                $error = $this->serializer->serialize(['errors' => 'CUSTOMER TABLE EMPTY'], 'json');

                return new JsonResponse($error, Response::HTTP_NO_CONTENT, ['Content-Type' => 'application/json'], true);
            }

            $paginationData = [
                'total_customers' => $totalCustomers,
                'page' => $page,
                'per_page' => $perPage,
                'customers' => $customers,
            ];

            $customers = $this->serializer->serialize($paginationData, 'json');

            $this->cache->set($cacheKey, $customers);
            $this->cache->expire($cacheKey, self::CACHE_TIMER);
        }

        return new JsonResponse($customers, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }

    #[Route('/customer/{id}', name: 'get_customer', methods: ['GET'])]
    public function getCustomerById(int $id): JsonResponse
    {
        $cacheKey = self::CUSTOMER_CACHE_KEY . $id;

        $customer = $this->cache->get($cacheKey);

        if (null === $customer) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($id);

            if (null == $customer) {
                $error = $this->serializer->serialize(['errors' => 'CUSTOMER NOT FOUND'], 'json');

                return new JsonResponse($error, Response::HTTP_NO_CONTENT, ['Content-Type' => 'application/json'], true);
            }

            $customer = $this->serializer->serialize($customer, 'json');

            $this->cache->set($cacheKey, $customer);
            $this->cache->expire($cacheKey, self::CACHE_TIMER);
        }

        return new JsonResponse($customer, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }

    #[Route('/customer', name: 'post_create_customer', methods: ['POST'])]
    public function createCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['customer_name'])) {
            $error = $this->serializer->serialize(['errors' => 'Post request incorrect. Incorrect key'], 'json');

            return new JsonResponse($error, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json'], true);
        }

        $response = $this->customerService->validateData($data, $this->validator, $this->serializer);
        $violations = json_decode($response->getContent(), true);
        if (!isset($violations['success'])) {
            return $response;
        }

        try {
            $fullName = $this->customerService->formatCustomerName($data['customer_name']);

            $customer = new Customer();
            $customer
                ->setFirstName($fullName['firstName'])
                ->setLastName($fullName['lastName']);

            $customerAddress = new CustomerAddress();
            $customerAddress->setAddress($data['customer_address'] ?? '');

            $customer->addAddress($customerAddress);

            $this->customerRepository->save($customer, true);

            $this->customerService->removeCache($this->cache, self::ALL_CUSTOMERS_CACHE_KEY);
        } catch (Exception $e) {
            $error = $this->serializer->serialize(['errors' => $e->getMessage()], 'json');

            return new JsonResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'application/json'], true);
        }
        $success = $this->serializer->serialize(['success' => 'Customer created successfully'], 'json');

        return new JsonResponse($success, Response::HTTP_CREATED, ['Content-Type' => 'application/json'], true);
    }

    #[Route('/customer/{id}', name: 'put_customer_update', methods: ['PUT'])]
    public function updateCustomer (Request $request): JsonResponse
    {
        $customerId = $request->get('id');

        $data = json_decode($request->getContent(), true);

        $response = $this->customerService->validateData($data, $this->validator, $this->serializer);
        $violations = json_decode($response->getContent(), true);
        if (!isset($violations['success'])) {
            return $response;
        }

        try {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (null == $customer) {
                $error = $this->serializer->serialize(['errors' => 'CUSTOMER NOT FOUND'], 'json');

                return new JsonResponse($error, Response::HTTP_NO_CONTENT, ['Content-Type' => 'application/json'], true);
            }

            $fullName = $this->customerService->formatCustomerName($data['customer_name'] ?? '');

            $customer
                ->setFirstName($fullName['firstName'])
                ->setLastName($fullName['lastName']);

            $customerAddress = new CustomerAddress();
            $customerAddress->setAddress($data['customer_address'] ?? '');

            $customer->addAddress($customerAddress);

            $this->customerRepository->save($customer, true);

            $customerCache = self::CUSTOMER_CACHE_KEY . $customerId;
            $this->customerService->removeCache($this->cache, self::ALL_CUSTOMERS_CACHE_KEY, $customerCache);
        } catch(Exception $e) {
            $error = $this->serializer->serialize(['errors' => $e->getMessage()], 'json');

            return new JsonResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'application/json'], true);
        }
        $success = $this->serializer->serialize(['success' => 'Customer updated successfully'], 'json');

        return new JsonResponse($success, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }

    #[Route('/customer/{id}', name: 'patch_customer', methods: ['PATCH'])]
    public function patchCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('id');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['customer_name']) && !isset($data['customer_address'])) {
            $error = ['errors' => 'Patch request incorrect. Incorrect keys'];

            return new JsonResponse($error, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json'], true);
        }

        $response = $this->customerService->validateData($data, $this->validator, $this->serializer);
        $violations = json_decode($response->getContent(), true);
        if (!isset($violations['success'])) {
            return $response;
        }

        try {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (null == $customer) {
                $error = $this->serializer->serialize(['error' => 'CUSTOMER NOT FOUND'], 'json');

                return new JsonResponse($error, Response::HTTP_NO_CONTENT, ['Content-Type' => 'application/json'], true);
            }

            if (isset($data['customer_name'])) {
                $fullName = $this->customerService->formatCustomerName($data['customer_name']);

                $customer
                    ->setFirstName($fullName['firstName'])
                    ->setLastName($fullName['lastName']);
            }

            if (isset($data['customer_address'])) {
                $customerAddress = new CustomerAddress();
                $customerAddress->setAddress($data['customer_address']);

                $customer->addAddress($customerAddress);
            }

            $this->customerRepository->save($customer, true);

            $customerCache = self::CUSTOMER_CACHE_KEY . $customerId;
            $this->customerService->removeCache($this->cache, self::ALL_CUSTOMERS_CACHE_KEY, $customerCache);
        } catch (Exception $e) {
            $error = $this->serializer->serialize(['errors' => $e->getMessage()], 'json');

            return new JsonResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $success = $this->serializer->serialize(['success' => 'Customer updated successfully'], 'json');

        return new JsonResponse($success, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }

    #[Route('/customer/{id}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('id');

        try {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                $error = $this->serializer->serialize(['errors' => 'CUSTOMER NOT FOUND'], 'json');

                return new JsonResponse($error, Response::HTTP_NO_CONTENT, ['Content-Type' => 'application/json'], true);
            }

            $this->customerRepository->remove($customer, true);

            $customerCache = self::CUSTOMER_CACHE_KEY . $customerId;
            $this->customerService->removeCache($this->cache, self::ALL_CUSTOMERS_CACHE_KEY, $customerCache);
        } catch (Exception $e) {
            $error = $this->serializer->serialize(['errors' => $e->getMessage()], 'json');

            return new JsonResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'application/json'], true);
        }
        $success = $this->serializer->serialize(['success' => 'CUSTOMER DELETED SUCCESSFULLY'], 'json');

        return new JsonResponse($success, Response::HTTP_OK, ['Content-Type' => 'application/json'], true);
    }
}
