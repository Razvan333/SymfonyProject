<?php

namespace App\Command;

use App\Consumer\RabbitMqMessageConsumer;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:consumeRabbitMqMessagesCommand',
    description: 'Consume messages from RabbitMQ queue',
)]
class RabbitMqMessagesCommand extends Command
{
    private RabbitMqMessageConsumer $rabbitMqMessageConsumer;

    public function __construct(
        RabbitMqMessageConsumer $rabbitMqMessageConsumer,
    ) {
        parent::__construct();

        $this->rabbitMqMessageConsumer = $rabbitMqMessageConsumer;
    }

    /*
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Consuming messages from RabbitMQ...');

        try {
            $this->rabbitMqMessageConsumer->consume();
        } catch (Exception $e) {
            $output->writeln('An error occurred while consuming messages:');
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }

        $output->writeln('Finished consuming messages.');

        return Command::SUCCESS;
    }
}
