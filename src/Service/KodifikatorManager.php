<?php

namespace Kodifikator\Service;

use Generator;
use Doctrine\ORM\EntityManagerInterface;
use Kodifikator\Entity\Kodifikator;
use Kodifikator\Entity\KodifikatorUpload;
use Kodifikator\Repository\KodifikatorRepository;


/**
 * Service class to manage Kodifikator and KodifikatorUpload entities.
 *
 * Handles importing data into the Kodifikator table,
 * managing upload metadata, and updating statuses.
 */
class KodifikatorManager
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Imports an array of data rows into the Kodifikator entity.
     * Uses an upsert strategy (insert or update).
     *
     * @param array $rows Associative array of data to import
     * @return int Number of processed records
     */
    public function import(Generator $rows): int
    {
        /** @var KodifikatorRepository $repo */
        $repo = $this->em->getRepository(Kodifikator::class);
        return $repo->upsertBatch($rows);
    }

    /**
     * Finds a KodifikatorUpload entity by its XLSX URL.
     *
     * @param string $url XLSX file URL
     * @return KodifikatorUpload|null Found entity or null
     */
    public function findByXlsxUrl(string $url): ?KodifikatorUpload
    {
        return $this->em->getRepository(KodifikatorUpload::class)
            ->findOneBy(['xlsxUrl' => $url]);
    }

    /**
     * Returns the most recently updated KodifikatorUpload entry.
     *
     * @return KodifikatorUpload|null The latest entity or null
     */
    public function findLatest(): ?KodifikatorUpload
    {
        return $this->em->getRepository(KodifikatorUpload::class)
            ->findOneBy([], ['updatedAt' => 'DESC']);
    }

    /**
     * Updates the status and updatedAt timestamp of the given upload entity.
     *
     * @param KodifikatorUpload $upload The upload entity to update
     * @param string $status New status (e.g., "downloaded", "updated")
     * @return void
     */
    public function updateStatus(KodifikatorUpload $upload, string $status): void
    {
        $upload = $this->em->merge($upload);
        $upload->setStatus($status);
        $upload->setUpdatedAt(new \DateTimeImmutable());
        // $this->em->persist($upload);
        // $this->em->flush();
    }

    /**
     * Creates or updates a KodifikatorUpload record based on XLSX URL.
     *
     * @param string $date Document date or title
     * @param array $documents Document metadata array with keys like 'xlsx', 'pdf', 'docx'
     * @return KodifikatorUpload The created or updated entity
     * @throws \InvalidArgumentException If XLSX URL is missing
     */
    public function save(string $date, array $documents): KodifikatorUpload
    {
        $xlsx = $documents['xlsx']['href'] ?? null;
        $upload = $this->findByXlsxUrl($xlsx);

        if (!$upload) {
            $upload = new KodifikatorUpload();
            $upload->setXlsxUrl($xlsx);
        }

        $upload
            ->setDate($date)
            ->setPdfUrl($documents['pdf']['href'] ?? '')
            ->setDocxUrl($documents['docx']['href'] ?? null)
            ->setDescription($date)
            ->setStatus('downloaded')
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($upload);

        return $upload;
    }


    /**
     * Flushes all pending changes to the database.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->em->flush();
    }

}
