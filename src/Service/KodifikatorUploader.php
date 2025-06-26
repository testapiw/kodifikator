<?php

namespace Kodifikator\Service;

use Kodifikator\Entity\KodifikatorUpload;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Service to fetch and store Kodifikator XLSX files from remote source.
 *
 * This service uses the KodifikatorParser to retrieve document URLs,
 * downloads XLSX files to local storage if not already present,
 * and persists information about the uploads in the database.
 *
 * Usage requires GuzzleHTTP client and Symfony Filesystem component.
 *
 * Example service configuration in services.yaml:
 * services:
 *     Kodifikator\Service\KodifikatorUploader:
 *         arguments:
 *             $storagePath: '%env(resolve:KODIFIKATOR_PATH)%'
 *
 * Ensure proper permissions for the storage directory:
 * sudo chown www-data:www-data var/kodifikator
 * 
 *  composer require guzzlehttp/guzzle
 */
class KodifikatorUploader
{
    private Client $client;
    
    /**
     * @param EntityManagerInterface $em Doctrine entity manager for persistence
     * @param KodifikatorParser $parser Parser service to extract file links
     * @param string $storagePath Path where XLSX files will be saved locally
     */
    public function __construct(
        private EntityManagerInterface $em,
        private KodifikatorParser $parser,
        private string $storagePath
    ) {
        $this->storagePath = rtrim($storagePath, '/') . '/';
    }

    /**
     * Fetches document links from remote source and downloads new XLSX files.
     *
     * Checks existing uploads to avoid duplicates, downloads missing files,
     * and saves upload metadata into the database.
     *
     * @return void
     */
    public function fetch(): void
    {
        $links = $this->parser->fetchAndParse();
        $repo = $this->em->getRepository(KodifikatorUpload::class);

        foreach ($links as $date => $documents) {
            $xlsx = $documents['xlsx']['href'] ?? null;

            if (!$xlsx|| $repo->findOneBy(['xlsxUrl' => $xlsx])) {
                // File already exists or no xlsx link found — skip
                continue;
            }

            $fileSaved = $this->downloadFiles($xlsx);
            if ($fileSaved) {
                $this->saveInfo($date, $documents);
            }
        }

        $this->em->flush();
    }

    /**
     * Downloads a remote XLSX file to local storage if not already present.
     *
     * @param string $xlsx URL of the XLSX file to download
     * @return bool True if file is saved or already exists, false on failure
     */
    private function downloadFiles(string $xlsx): bool
    {
        $client = new Client();
        $fs = new Filesystem();

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0750, true);
        }

        $filename = basename(parse_url($xlsx, PHP_URL_PATH));
        $localPath = $this->storagePath . $filename;

        if ($fs->exists($localPath)) {
            // File already downloaded
            return true;
        }

        try {
            $response = $client->request('GET', $xlsx);
            if ($response->getStatusCode() !== 200) {
                return false;
            }
            $content = $response->getBody()->getContents();

            $fs->dumpFile($localPath, $content);

            chmod($localPath, 0640);
        } catch (\Exception $e) {
            // You can add logging here
            return false;
        }
     
        return true;
    }


    /**
     * Saves metadata about the downloaded documents into the database.
     *
     * Creates or updates a KodifikatorUpload entity with URLs and status.
     *
     * @param string $date Title or date of the document set
     * @param array $documents Array of document metadata, keys like 'xlsx', 'pdf', 'docx'
     * @return void
     */
    private function saveInfo(string $date, array $documents): void
    {
        $xlsx = $documents['xlsx']['href'] ?? null;

        if (!$xlsx) {
            return;
        }

        $repo = $this->em->getRepository(KodifikatorUpload::class);

        $upload = $repo->findOneBy(['xlsxUrl' => $xlsx]);

        if (!$upload) {
            $upload = new KodifikatorUpload();
            $upload
                ->setXlsxUrl($xlsx)
                ->setPdfUrl($documents['pdf']['href'] ?? '')
                ->setDocxUrl($documents['docx']['href'] ?? null)
                ->setDescription("$date")
                ->setDate($date);
        }

        $upload
            ->setStatus('downloaded')
            ->setUpdatedAtAt(new \DateTimeImmutable());

        $this->em->persist($upload);
    }

}
