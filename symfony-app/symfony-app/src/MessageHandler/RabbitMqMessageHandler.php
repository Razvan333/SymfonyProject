<?php

namespace App\MessageHandler;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Message\RabbitMqMessage;
use App\Validator\Data;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RabbitMqMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @throws Exception
     */
    public function __invoke(RabbitMqMessage $message): void
    {
        $data = json_decode($message->getData(), true);

        $dataForValidation = [
            0 => $data['customer_id'],
            1 => $data['customer_address'],
            2 => $data['customer_name']
        ];
        $violations = $this->validator->validate($dataForValidation, new Data());
        if (count($violations)) {
            foreach ($violations as $violation) {
                throw new Exception($violation->getMessage());
            }
        }

        try {
            $customer = new Customer();
            $customer->setId($data['customer_id']);
            $customer->setCustomerName($data['customer_name']);

            $customerAddress = new CustomerAddress();
            $customerAddress
                ->setCustomerId($customer)
                ->setAddress($data['customer_address']);

            $customer->setCustomerAddress($customerAddress);

            $this->entityManager->persist($customer);
            $this->entityManager->persist($customerAddress);

            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->entityManager->beginTransaction();

            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw new Exception($e->getMessage());
        }
    }
}