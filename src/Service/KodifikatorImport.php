<?php

namespace Kodifikator\Service;

use Generator;
use Kodifikator\Entity\Kodifikator;
use Kodifikator\Repository\KodifikatorRepository;
use Psr\Log\LoggerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Doctrine\ORM\EntityManagerInterface;
use Kodifikator\Entity\KodifikatorUpload;

/**
 * composer require phpoffice/phpspreadsheet
 * sudo apt install php8.4-gd php8.4-zip php8.4-xml php8.4-mbstring
 */
class KodifikatorImport
{
    private string $uploadUrl = '';

    public function __construct(
        private EntityManagerInterface $em,
        private string $storagePath,
        private LoggerInterface $logger 
    ) {
        $this->storagePath = rtrim($storagePath, '/') . '/';
    }


    public function import(): void
    {
        $start = microtime(true);
        $this->logger->info('Починається імпорт кодифікатора');
        
        $filepath = $this->getFile();
        $this->logger->info('Обрабатывается файл', ['file' => $filepath]);

        $importStart = microtime(true);
        /** @var KodifikatorRepository $repo */
        $repo = $this->em->getRepository(Kodifikator::class);
        $processedCount = $repo->upsertBatch($this->rowGenerator($filepath));
        $this->logger->info("Оброблено записів: $processedCount");

        $this->setStatusUpdated();

        $importTime = microtime(true) - $importStart;
        $this->logger->info("Імпорт завершено за {$importTime} секунд");

        $totalTime = microtime(true) - $start;
        $this->logger->info("Загальний час виконання скрипта: {$totalTime} секунд");
    }

    private function getFile()
    {
        $entity = $this->em->getRepository(KodifikatorUpload::class)
            ->findOneBy([], ['updatedAt' => 'DESC']);

        $this->uploadUrl = $entity->getXlsxUrl();

        if (!$this->uploadUrl) {
            $this->logger->error('No uploaded Kodifikator XLSX file found.');
            throw new \RuntimeException('No uploaded Kodifikator XLSX file found.');
        }

        $filename = basename(parse_url($this->uploadUrl, PHP_URL_PATH));
        $filepath = $this->storagePath . $filename;

        if (!file_exists($filepath)) {
            $this->logger->error("File not found or not readable: $filepath");
            throw new \RuntimeException("File not found: $filepath");
        }

        return $filepath;
    }


    private function rowGenerator(string $filepath): Generator
    {
        try {
            $spreadsheet = IOFactory::load($filepath);
        } catch (\Exception $e) {
            $this->logger->error("Failed to load spreadsheet: " . $e->getMessage());
            throw new \RuntimeException("Failed to load spreadsheet: " . $e->getMessage());
        }
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator(1) as $row) {

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $this->sanitize($cell->getValue());
            }

            if (isset($cells[0]) && str_starts_with(trim((string) $cells[0]), 'UA')) {
                yield $cells;
            }
        }
    }

    private function setStatusUpdated(): void
    {
        $entity = $this->em->getRepository(KodifikatorUpload::class)->findOneBy(['xlsxUrl' => $this->uploadUrl]);
        
        if (!$entity) {
            $this->logger->error('No uploaded Kodifikator XLSX file found.');
            throw new \RuntimeException('No uploaded Kodifikator XLSX file found.');
        }

        $entity->setStatus('updated');
        $this->em->persist($entity);
        $this->em->flush();

        $this->em->clear();
    }


    private function sanitize(?string $value): string
    {
        $value = (string) ($value ?? '');

        $value = preg_replace([
            '/[\x00-\x1F\x7F]+/u',         // Удаляем управляющие символы
            '/^\s+|\s+$/u',                // Обрезаем пробелы по краям
            '/\s+/u'                       // Заменяем множественные пробелы одним
        ], [
            '', '', ' '
        ], $value);

        return $value;
    }

}

