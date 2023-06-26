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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CustomerPatchController extends AbstractController
{
    private EntityManager $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
      EntityManager $entityManager,
      ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('/customer/patch/{id}', name: 'patch_customer', methods: ['PATCH'])]
    public function patchCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('id');
        $data = json_decode($request->getContent(), true);

        if (!isset($data['customer_name'])) {
            return new JsonResponse('Patch request incorrect. Incorrect key', Response::HTTP_BAD_REQUEST);
        }

        $dataForValidation = [
            'id' => $customerId,
            'customer_name' => $data['customer_name']
        ];
        $violations = $this->validator->validate($dataForValidation, new Data());
        if (count($violations) > 0) {
            $errors = [];
            foreach($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
            if (!$customer) {
                return new JsonResponse(['error' => 'Customer with id: ' . $customerId . ' not found'], Response::HTTP_NOT_FOUND);
            }

            $customerOldName = $customer->getCustomerName();

            $customer->setCustomerName($data['customer_name']);

            $this->entityManager->beginTransaction();

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();

            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $responseData = [
            'success' => 'Customer wth id: ' . $customerId . ' patch successfully',
            'old_name' => $customerOldName,
            'new_name' => $customer->getCustomerName()
        ];

        return new JsonResponse($responseData, Response::HTTP_OK);
    }
}
