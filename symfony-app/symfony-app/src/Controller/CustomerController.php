<?php

namespace App\Controller;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Predis\Client;
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
    private Client $cache;
    private EntityManagerInterface $entityManager;
    private const CACHE_TIMER = 86400;

    public function __construct (
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        Client $cache
    ) {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    #[Route('/customer', name: 'get_all_customer', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $cacheKey = 'all_customers';

        $customers = $this->cache->get($cacheKey);

        if (null === $customers) {
            $customers = $this->entityManager->getRepository(Customer::class)->findAll();
            if (!$customers) {
                return new JsonResponse(['error' => 'CUSTOMER TABLE EMPTY'], Response::HTTP_NOT_FOUND, [], true);
            }

            $this->cache->set($cacheKey, $customers);
            $this->cache->expire($cacheKey, self::CACHE_TIMER);
        }

        return new JsonResponse($customers, Response::HTTP_OK, [], true);
    }

    #[Route('/customer/{id}', name: 'get_customer', methods: ['GET'])]
    public function getCustomerById(int $id): JsonResponse
    {
        $cacheKey = 'customer_' . $id;

        $customer = $this->cache->get($cacheKey);

        if (null === $customer) {
            $customer = $this->entityManager->getRepository(Customer::class)->find($id);

            if (!$customer) {
                return new JsonResponse(['error' => 'CUSTOMER WITH ID: ' . $id .' NOT FOUND'], Response::HTTP_NOT_FOUND, [], true);
            }

            $this->cache->set($cacheKey, $customer);
            $this->cache->expire($cacheKey, self::CACHE_TIMER);
        }

        return new JsonResponse($customer, Response::HTTP_OK, [], true);
    }
}
