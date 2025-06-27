<?php

namespace Kodifikator\Service;

use Kodifikator\Domain\KodifikatorParser;
use Kodifikator\Entity\KodifikatorUpload;
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
        private KodifikatorManager $uploadManager,
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
        $links = $this->parser->process();

        foreach ($links as $date => $documents) {
            $xlsx = $documents['xlsx']['href'] ?? null;

            if (!$xlsx) {
                // log
                // throw new \InvalidArgumentException('Missing XLSX URL in document metadata.');
                continue;
            }

            if ($this->uploadManager->findByXlsxUrl($xlsx)) {
                // File already exists or no xlsx link found — skip
                continue;
            }

            if ($this->downloadFiles($xlsx)) {
                $this->uploadManager->save($date, $documents);
            }
        }

        $this->uploadManager->flush();
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
        } 
        catch (\Exception $e) {
            // You can add logging here
            return false;
        }
     
        return true;
    }

}
