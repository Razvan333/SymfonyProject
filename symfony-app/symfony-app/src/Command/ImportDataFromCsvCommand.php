<?php

namespace App\Command;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Repository\CustomerRepository;
use App\Validator\Data;
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
    private const BATCH_SIZE = 1000;

    private ValidatorInterface $validator;

    private CustomerRepository $customerRepository;
    public function __construct(
        ValidatorInterface $validator,
        CustomerRepository $customerRepository
    ) {
        $this->validator = $validator;
        $this->customerRepository = $customerRepository;
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

            $customerAddressIndex = array_search('address', $headers);

            if ($customerAddressIndex === false) {
                $io->error('CSV file is missing required column: address');

                return Command::FAILURE;
            }

            while (($row = fgetcsv($file, self::BATCH_SIZE,',')) !== false) {
                $dataForValidation = [
                    'customer_address' => $row[$customerAddressIndex]
                ];
                $violations = $this->validator->validate($dataForValidation, new Data());
                if (count($violations)) {
                    foreach ($violations as $violation) {
                        $io->error($violation->getPropertyPath() . ': ' . $violation->getMessage());
                    }

                    return Command::FAILURE;
                }

                $address = $row[$customerAddressIndex];

                $customer = new Customer();

                $customerAddress = new CustomerAddress();
                $customerAddress
                    ->setAddress($address);

                $customer->addAddress($customerAddress);

                $this->customerRepository->save($customer);

                $batchCount++;
                if ($batchCount % self::BATCH_SIZE === 0) {
                    $this->customerRepository->flushAndClear();
                }
            }

            $this->customerRepository->flushAndClear();
        } catch (\Exception $e) {
            $io->error('Error importing data: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Data inserted successfully');

        return Command::SUCCESS;
    }
}
