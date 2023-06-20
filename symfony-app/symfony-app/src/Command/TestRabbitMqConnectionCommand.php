<?php

namespace App\Command;

use App\Message\RabbitMqMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:test-rabbitmq-connection',
    description: 'Test RabbitMQ Connection',
)]
class TestRabbitMqConnectionCommand extends Command
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:test-rabbitmq-connection')
            ->setDescription('Test RabbitMQ Connection');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = ['message' => 'This is a test message'];

        $message = new RabbitMqMessage($data);
        $this->messageBus->dispatch($message);

        $output->writeln('WORKS CONNECTION');

        return Command::SUCCESS;
    }
}