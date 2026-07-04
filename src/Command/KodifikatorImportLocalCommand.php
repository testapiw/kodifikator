<?php

namespace App\Utility\Kodifikator\Command;

use Kodifikator\Service\KodifikatorLocalImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * php bin/console kodifikator:import-local kodifikator-22-06-2026.xlsx "Кодифікатор 22.06.2026"
 * ** /usr/bin/php8.2
 */
#[AsCommand(
    name: 'kodifikator:import-local',
    description: 'Локальний імпорт конкретного XLSX кодифікатора з папки var/kodifikator',
)]
class KodifikatorImportLocalCommand extends Command
{
    private string $storagePath;

    public function __construct(
        private readonly KodifikatorLocalImporter $localImporter,
        #[Autowire('%env(resolve:KODIFIKATOR_PATH)%')] string $storagePath
    ) {
        parent::__construct();
        $this->storagePath = rtrim($storagePath, '/') . '/';
    }

    protected function configure(): void
    {
        $this->addArgument('filename', InputArgument::REQUIRED, 'Ім\'я файлу в папці var/kodifikator/ (наприклад: kodifikator-22-06-2026.xlsx)');
        $this->addArgument('date', InputArgument::REQUIRED, 'Дата публікації для реєстру (наприклад: "Кодифікатор 22.06.2026")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filename = $input->getArgument('filename');
        $date = $input->getArgument('date');
        
        $fullPath = $this->storagePath . $filename;

        $output->writeln(sprintf('<info>Starting local import for: %s</info>', $date));

        if (!file_exists($fullPath)) {
            $output->writeln(sprintf('<error>Error: Файл не знайдено за шляхом: %s</error>', $fullPath));
            return Command::FAILURE;
        }

        try {
            $this->localImporter->handleLocalImport($filename, $date);

            $output->writeln('<info>Done.</info>');
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}