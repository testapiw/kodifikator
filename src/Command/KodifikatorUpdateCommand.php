<?php

namespace App\Utility\Kodifikator\Command;

use Kodifikator\Service\KodifikatorUploader;
use Kodifikator\Service\KodifikatorImport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Symfony Console Command to update the Kodifikator data.
 * 
 * This command runs the parsing and downloading of the latest XLSX codifier file,
 * then imports the data into the system.
 * 
 * Usage:
 *   php bin/console kodifikator:update
 */
#[AsCommand(
    name: 'kodifikator:update',
    description: 'Парсинг и загрузка свежего XLSX кодификатора.',
)]
class KodifikatorUpdateCommand extends Command
{
    /**
     * @param KodifikatorUploader $uploader Service responsible for fetching and downloading XLSX files
     * @param KodifikatorImport $import Service responsible for importing XLSX data into the database
     */
    public function __construct(
        private readonly KodifikatorUploader $uploader,
        private readonly KodifikatorImport $import
    ) {
        parent::__construct();
    }

    /**
     * Executes the command.
     *
     * It calls the uploader to fetch/download the latest XLSX,
     * then triggers the import process.
     * Outputs status messages and catches exceptions to display errors.
     *
     * @param InputInterface $input Command input
     * @param OutputInterface $output Command output
     * @return int Exit code (Command::SUCCESS or Command::FAILURE)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting Kodifikator parsing and downloading...</info>');

        try {
            $this->uploader->fetch();
            $this->import->import();
            $output->writeln('<info>Done.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
