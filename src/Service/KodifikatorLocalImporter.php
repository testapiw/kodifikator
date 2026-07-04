<?php

namespace Kodifikator\Service;

class KodifikatorLocalImporter
{
    public function __construct(
        private readonly KodifikatorManager $uploadManager,
        private readonly KodifikatorImport $import
    ) {}

    /**
     * Handles the local import of a Kodifikator XLSX file.
     *
     * This method simulates an upload by creating a fake URL for the local file,
     * checks if the file has already been processed, and if not, saves the metadata
     * and triggers the import process.
     *
     * @param string $filename The name of the local XLSX file
     * @param string $documentDate The date associated with the document (used for metadata)
     */
    public function handleLocalImport(string $filename, string $documentDate): void
    {
        $fakeXlsxUrl = $filename; 

        if (!$this->uploadManager->findByXlsxUrl($fakeXlsxUrl)) {
            $documents = [
                'xlsx' => [
                    'href' => $fakeXlsxUrl,
                    'text' => $documentDate
                ],
                'pdf' => ['href' => ''],
                'docx' => ['href' => null]
            ];

            $this->uploadManager->save($documentDate, $documents);
            $this->uploadManager->flush();
        }

        $this->import->import();
    }
}