<?php

namespace App\Command;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Validator\Data;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:ImportDataFromCsv',
    description: 'read data from .csv and insert it into databases',
)]
class ImportDataFromCsvCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private const BATCH_SIZE = 1000;

    private ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:ImportDataFromCsv')
            ->setDescription('import data from .csv')
            ->addArgument('file', InputArgument::REQUIRED, 'path to csv')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('import .CSV');

        $filePath = $input->getArgument('file');

        $file = fopen($filePath, 'r');
        if (!$file) {
            $io->error('FAILED TO OPEN CSV: ' . $filePath);

            return Command::FAILURE;
        }

        $this->entityManager->beginTransaction();

        try {
            $batchCount = 0;

            fgetcsv($file);

            while (($row = fgetcsv($file, 1000,',')) !== false) {
                if (isset($row[0]) && isset($row[1])) {
                    $dataForValidation = [
                        'id' => $row[0],
                        'customer_address' => $row[1]
                    ];
                    $violations = $this->validator->validate($dataForValidation, new Data());
                    if (count($violations)) {
                        foreach ($violations as $violation) {
                            $io->error($violation->getPropertyPath() . ': ' . $violation->getMessage());
                        }

                        return Command::FAILURE;
                    }

                    $customerId = $row[0];
                    $address = $row[1];

                    $customer = new Customer();
                    $customer->setId($customerId);

                    $customerAddress = new CustomerAddress();
                    $customerAddress
                        ->setCustomerId($customer)
                        ->setAddress($address);

                    $customer->setCustomerAddress($customerAddress);

                    $this->entityManager->persist($customer);
                    $this->entityManager->persist($customerAddress);

                    $batchCount++;
                    if ($batchCount % self::BATCH_SIZE === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                        $batchCount = 0;
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('Error importing data: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Data inserted successfully');

        return Command::SUCCESS;
    }
}
