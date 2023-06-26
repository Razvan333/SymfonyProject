<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Validator\Data;
use Exception;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerPostController extends AbstractController
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManager $entityManager;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator, EntityManager $entityManager)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    #[Route('/customer/post', name: 'post_create_customer', methods: ['POST'])]
    public function createCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dataForValidation = [
            'id' => $data['id'],
            'customer_name' => $data['customer_name']
        ];
        $violations = $this->validator->validate($dataForValidation, new Data());
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $customer = new Customer();
            $customer
                ->setId($data['id'])
                ->setCustomerName($data['customer_name']);

            $this->entityManager->beginTransaction();

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            return new JsonResponse("['error' => " . $e->getMessage() . "]", Response::HTTP_INTERNAL_SERVER_ERROR, [], true);
        }

        $responseJson = $this->serializer->serialize($customer, 'json');

        return new JsonResponse('Customer created: ' . $responseJson, Response::HTTP_CREATED, [], true);
    }
}
