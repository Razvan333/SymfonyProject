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
        $this->addArgument('file', InputArgument::REQUIRED, 'path to csv');
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

        try {
            $batchCount = 0;

            $headers = fgetcsv($file);
            if ($headers === false) {
                $io->error('CSV file has no header');

                return Command::FAILURE;
            }

            $customerIdIndex = array_search('customer_id', $headers);
            $customerAddressIndex = array_search('address', $headers);

            if ($customerIdIndex === false || $customerAddressIndex === false) {
                $io->error('CSV file is missing required columns: customer_id, address');

                return Command::FAILURE;
            }

            while (($row = fgetcsv($file, self::BATCH_SIZE,',')) !== false) {
                if (count($row) !== 2) {
                    $io->error('Invalid row format: Insufficient columns');

                    return Command::FAILURE;
                }

                $dataForValidation = [
                    'id' => $row[$customerIdIndex],
                    'customer_address' => $row[$customerAddressIndex]
                ];

                $violations = $this->validator->validate($dataForValidation, new Data());
                if (count($violations)) {
                    foreach ($violations as $violation) {
                        $io->error($violation->getPropertyPath() . ': ' . $violation->getMessage());
                    }

                    return Command::FAILURE;
                }

                $customerId = (int) $row[$customerIdIndex];
                $address = $row[$customerAddressIndex];

                $customer = new Customer();
                $customer->setCustomerId($customerId);

                $customerAddress = new CustomerAddress();
                $customerAddress
                    ->setCustomerId($customerId)
                    ->setAddress($address);

                $customer->addAddress($customerAddress);

                $this->entityManager->beginTransaction();

                $this->entityManager->persist($customer);

                $batchCount++;
                if ($batchCount % self::BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
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
