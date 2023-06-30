<?php

namespace App\Consumer;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Repository\CustomerRepository;
use App\Validator\Data;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RabbitMqMessageConsumer
{
    private CustomerRepository $customerRepository;
    private ValidatorInterface $validator;
    private AMQPStreamConnection $rabbitMqConnection;
    private string $queueName;

    public function __construct(
        CustomerRepository $customerRepository,
        ValidatorInterface $validator,
        AMQPStreamConnection $rabbitMqConnection,
        string $queueName
    ) {
        $this->customerRepository = $customerRepository;
        $this->validator = $validator;
        $this->rabbitMqConnection = $rabbitMqConnection;
        $this->queueName = $queueName;
    }

    /**
     * @throws Exception
     */
    public function consume(): void
    {
        $channel = $this->rabbitMqConnection->channel();

        $channel->queue_declare($this->queueName, false, true, false, false);

        $channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($channel) {
                try {
                    $this->__invoke($message, $channel);

                    $message->ack();
                } catch (Exception $e) {
                   throw new Exception($e->getMessage());
                }
            }
        );

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }

        $channel->close();
        $this->rabbitMqConnection->close();
    }

    /**
     * @throws Exception
     */
    public function __invoke(AMQPMessage $message, AMQPChannel $channel): void
    {
        try {
            $data = json_decode($message->getBody(), true);

            $violations = $this->validator->validate($data, new Data());
            if (count($violations)) {
                foreach ($violations as $violation) {
                    $channel->basic_reject($message->getDeliveryTag(), false);
                    throw new Exception($violation->getMessage());
                }
            }

            $fullName = explode(' ', $data['customer_name'] ?? '');
            $lastName = array_pop($fullName);
            $firstName = implode(' ', $fullName);

            $customer = new Customer();
            $customer
                ->setFirstName($firstName)
                ->setLastName($lastName);

            $customerAddress = new CustomerAddress();
            $customerAddress
                ->setAddress($data['customer_address'] ?? '');

            $customer->addAddress($customerAddress);

            $this->customerRepository->save($customer, true);
        } catch (Exception $e) {
            throw new Exception('Message not processed correctly ' . $e->getMessage());
        }
    }
}
