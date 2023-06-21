<?php

namespace App\Controller;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CustomerController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private RedisTagAwareAdapter $cache;
    private EntityManagerInterface $entityManager;
    private const CACHE_TIMER = 86400;

    public function __construct (
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;

        $client = RedisAdapter::createConnection(
            'redis://redis:6379'
        );

        $this->cache = new RedisTagAwareAdapter($client);
    }

    #[Route('/customer', name: 'get_all_customer', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $cacheKey = 'all_customers';

        try {
            $customers = $this->cache->get($cacheKey, function (ItemInterface $item) {
                $customers = $this->entityManager->getRepository(Customer::class)->findAll();

                if (!$customers) {
                    throw $this->createNotFoundException('Customers table is empty');
                }

                $serializedCustomers = $this->serializer->serialize($customers, 'json');

                $item->expiresAfter(self::CACHE_TIMER);

                return $serializedCustomers;
            });
        } catch (InvalidArgumentException $iae) {
            return new JsonResponse(['error' => 'Invalid argument: ' .$iae->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $nfhe) {
            return new JsonResponse(['error' => $nfhe->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($customers, Response::HTTP_OK, [], true);
    }

    #[Route('/customer/{id}', name: 'get_customer', methods: ['GET'])]
    public function getCustomerById(int $id): JsonResponse
    {
        $cacheKey = 'customer_' . $id;

        try {
            $customer = $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
                $customer = $this->entityManager->getRepository(Customer::class)->find($id);

                if (!$customer) {
                    throw $this->createNotFoundException('Customer not found.');
                }

                $serializeCustomer = $this->serializer->serialize($customer, 'json');

                $item->expiresAfter(self::CACHE_TIMER);

                return $serializeCustomer;
            });
        } catch (InvalidArgumentException $iae) {
            return new JsonResponse(['error' => 'Invalid argument: ' .$iae->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (NotFoundHttpException $nfhe) {
            return new JsonResponse(['error' => $nfhe->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($customer, Response::HTTP_OK, [], true);
    }
}
