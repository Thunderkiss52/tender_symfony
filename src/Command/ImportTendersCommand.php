<?php

namespace App\Command;

use App\Entity\Tender;
use App\Repository\TenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:import-tenders',
    description: 'Imports tenders from a CSV file into the database'
)]
class ImportTendersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private TenderRepository $tenderRepository;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TenderRepository $tenderRepository,
        ValidatorInterface $validator
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->tenderRepository = $tenderRepository;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $io->error(sprintf('File "%s" does not exist or is not readable.', $filePath));
            return Command::FAILURE;
        }

        $io->title('Importing tenders from CSV');

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error('Failed to open the CSV file.');
            return Command::FAILURE;
        }

        $currentLocale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'ru_RU.UTF-8');

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            setlocale(LC_ALL, $currentLocale);
            $io->error('Failed to read headers from the CSV file.');
            return Command::FAILURE;
        }

        $headers = array_map(function ($header) {
            return trim(mb_strtolower($header, 'UTF-8'));
        }, $headers);

        $io->note('Headers found: ' . implode(', ', $headers));

        $requiredHeaders = [
            mb_strtolower('Внешний код', 'UTF-8'),
            mb_strtolower('Номер', 'UTF-8'),
            mb_strtolower('Статус', 'UTF-8'),
            mb_strtolower('Название', 'UTF-8'),
            mb_strtolower('Дата изм.', 'UTF-8')
        ];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        if (!empty($missingHeaders)) {
            fclose($handle);
            setlocale(LC_ALL, $currentLocale);
            $io->error('Invalid CSV format. Missing required headers: ' . implode(', ', $missingHeaders));
            return Command::FAILURE;
        }

        $externalCodeIndex = array_search(mb_strtolower('Внешний код', 'UTF-8'), $headers);
        $numberIndex = array_search(mb_strtolower('Номер', 'UTF-8'), $headers);
        $statusIndex = array_search(mb_strtolower('Статус', 'UTF-8'), $headers);
        $nameIndex = array_search(mb_strtolower('Название', 'UTF-8'), $headers);
        $createdAtIndex = array_search(mb_strtolower('Дата изм.', 'UTF-8'), $headers);

        $successCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            if (count($row) < count($headers)) {
                $io->warning(sprintf('Line %d: Invalid number of columns, expected %d, got %d, attempting to fix.', $lineNumber, count($headers), count($row)));
                $fixedRow = [];
                for ($i = 0; $i < $nameIndex; $i++) {
                    $fixedRow[] = $row[$i] ?? '';
                }
                $nameParts = array_slice($row, $nameIndex, $createdAtIndex - $nameIndex);
                $fixedRow[] = implode(',', $nameParts);
                for ($i = $createdAtIndex; $i < count($headers); $i++) {
                    $fixedRow[] = $row[$i] ?? '';
                }
                $row = $fixedRow;
            }

            if (count($row) !== count($headers)) {
                $io->warning(sprintf('Line %d: Invalid number of columns after fix, expected %d, got %d, skipping.', $lineNumber, count($headers), count($row)));
                $errorCount++;
                continue;
            }

            $externalCode = $row[$externalCodeIndex];
            $number = $row[$numberIndex];
            $status = $row[$statusIndex];
            $name = $row[$nameIndex];
            $createdAt = $row[$createdAtIndex];

            $io->note(sprintf('Line %d: externalCode="%s", number="%s", status="%s", name="%s", createdAt="%s"', $lineNumber, $externalCode, $number, $status, $name, $createdAt));

            if (empty($externalCode) || empty($number) || empty($status) || empty($name) || empty($createdAt)) {
                $io->warning(sprintf('Line %d: Empty field detected, skipping.', $lineNumber));
                $errorCount++;
                continue;
            }

            $tender = new Tender();
            $tender->setExternalCode((int)$externalCode);
            $tender->setNumber($number);
            $tender->setStatus($status);
            $tender->setName($name);

            try {
                $dateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $createdAt);
                if ($dateTime === false) {
                    throw new \Exception('Invalid date format');
                }
                $tender->setDate($dateTime);
            } catch (\Exception $e) {
                $io->warning(sprintf('Line %d: Invalid date format "%s" (error: %s), skipping.', $lineNumber, $createdAt, $e->getMessage()));
                $errorCount++;
                continue;
            }

            $existingTender = $this->tenderRepository->findOneByAllFields((int)$externalCode, $number, $status, $name, $dateTime);
            if ($existingTender !== null) {
                $io->warning(sprintf('Line %d: Tender with externalCode="%s", number="%s", status="%s", name="%s", date="%s" already exists, skipping.', $lineNumber, $externalCode, $number, $status, $name, $createdAt));
                $duplicateCount++;
                continue;
            }

            $errors = $this->validator->validate($tender, null, ['Default']);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $io->warning(sprintf('Line %d: Validation failed: %s', $lineNumber, implode('; ', $errorMessages)));
                $errorCount++;
                continue;
            }

            $this->entityManager->persist($tender);
            $successCount++;
        }

        fclose($handle);
        setlocale(LC_ALL, $currentLocale);

        $this->entityManager->flush();

        $io->success(sprintf('Imported %d tenders successfully.', $successCount));
        if ($errorCount > 0) {
            $io->warning(sprintf('Skipped %d rows due to errors.', $errorCount));
        }
        if ($duplicateCount > 0) {
            $io->warning(sprintf('Skipped %d rows due to duplicates.', $duplicateCount));
        }

        return Command::SUCCESS;
    }
}