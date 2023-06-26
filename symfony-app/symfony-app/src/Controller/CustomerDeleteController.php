<?php

namespace App\Controller;

use App\Entity\Customer;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomerDeleteController extends AbstractController
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/customer/delete/{id}', name: 'delete_customer', methods: ['DELETE'])]
    public function deleteCustomer(Request $request): JsonResponse
    {
        $customerId = $request->get('id');

        try {
            $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);

            if (!$customer) {
                return new JsonResponse(['error' => 'Customer with id: ' . $customerId . ' not found'], Response::HTTP_NOT_FOUND);
            }
            $customerName = $customer->getCustomerName();

            $this->entityManager->beginTransaction();

            $addresses = $customer->getCustomerAddresses();
            foreach ($addresses as $address) {
                $this->entityManager->remove($address);
            }

            $this->entityManager->remove($customer);
            $this->entityManager->flush();

        } catch (Exception $e) {
            $this->entityManager->rollback();

            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse('DELETED successfully customer with id: ' . $customerId . ' and name: ' . $customerName , Response::HTTP_OK);
    }
}
