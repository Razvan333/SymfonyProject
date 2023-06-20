<?php

namespace App\Command;

use App\Message\RabbitMqMessage;
use App\MessageHandler\RabbitMqMessageHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

#[AsCommand(
    name: 'app:consumeRabbitMqMessagesCommand',
    description: 'Consume messages from RabbitMQ queue',
)]
class ConsumeRabbitMqMessagesCommand extends Command
{
    private RabbitMqMessageHandler $messageHandler;
    private AMQPStreamConnection $rabbitMqConnection;

    public function __construct(
        RabbitMqMessageHandler $messageHandler,
        AMQPStreamConnection $rabbitMqConnection
    ) {
        parent::__construct();

        $this->messageHandler = $messageHandler;
        $this->rabbitMqConnection = $rabbitMqConnection;
    }

    protected function configure()
    {
        $this->setName('app:consumeRabbitMqMessagesCommand')
            ->setDescription('Consume messages from RabbitMQ queue');
    }

    /*
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channel = $this->rabbitMqConnection->channel();
        $queueName = 'test_queue';

        $channel->queue_declare($queueName, false, true, false, false);

        $channel->basic_consume($queueName, '', false, false, false, false, function ($message) use ($channel) {
            try {
                $this->messageHandler->__invoke(new RabbitMqMessage($message->getBody()));
                $channel->basic_ack($message->delivery_info['delivery_tag']);
            } catch (\Exception $e) {
                echo "Exception: " . $e->getMessage() . PHP_EOL;
                return Command::FAILURE;
            }
            return Command::SUCCESS;
        });

        while (count($channel->callbacks) > 0) {
            $channel->wait();
        }

        $channel->close();
        $this->rabbitMqConnection->close();

        return Command::SUCCESS;
    }
}
