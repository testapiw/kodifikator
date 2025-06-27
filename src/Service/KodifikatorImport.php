<?php

namespace Kodifikator\Service;

use Generator;
use Kodifikator\Entity\Kodifikator;
use Kodifikator\Repository\KodifikatorRepository;
use Psr\Log\LoggerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Kodifikator\Entity\KodifikatorUpload;

/**
 * composer require phpoffice/phpspreadsheet
 * sudo apt install php8.4-gd php8.4-zip php8.4-xml php8.4-mbstring
 */
class KodifikatorImport
{
    private string $uploadUrl = '';

    public function __construct(
        private KodifikatorManager $uploadManager,
        private string $storagePath,
        private LoggerInterface $logger 
    ) {
        $this->storagePath = rtrim($storagePath, '/') . '/';
    }


    public function import(): void
    {
        $start = microtime(true);
        $this->logger->info('Починається імпорт кодифікатора');
        
        $upload = $this->uploadManager->findLatest();
        if (!$upload) {
            throw new \RuntimeException('No upload found');
        }

        if ($upload->getStatus() === 'updated') {
            $this->logger->info('Файл вже імпортовано.', [
                'xlsx' => $upload->getXlsxUrl(),
                'date' => $upload->getDate(),
            ]);
            return;
        }

        $filepath = $this->resolveFilePath($upload->getXlsxUrl());
        $this->logger->info('Обробляється файл', ['file' => $filepath]);

        $count = $this->uploadManager->import($this->rowGenerator($filepath));
        $this->logger->info("Оброблено записів: $count");

        $this->uploadManager->updateStatus($upload, 'updated');
        $this->uploadManager->flush();

        $elapsed = microtime(true) - $start;
        $this->logger->info("Імпорт завершено за: {$elapsed} секунд");
    }

    
    private function resolveFilePath(string $url): string
    {
        if (!$url) {
            $this->logger->error('No uploaded Kodifikator XLSX file found.');
            throw new \RuntimeException('No uploaded Kodifikator XLSX file found.');
        }

        $filename = basename(parse_url($url, PHP_URL_PATH));
        $path = $this->storagePath . $filename;

        if (!file_exists($path)) {
            $this->logger->error("File not found or not readable: $path");
            throw new \RuntimeException("File not found: $path");
        }

        return $path;
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

    private function sanitize(?string $value): string
    {
        $value = (string) ($value ?? '');

        $value = preg_replace([
            '/[\x00-\x1F\x7F]+/u',  // Remove control characters
            '/^\s+|\s+$/u',               
            '/\s+/u'                       
        ], [
            '', '', ' '
        ], $value);

        return $value;
    }

}

