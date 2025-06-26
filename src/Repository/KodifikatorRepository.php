<?php

namespace Kodifikator\Repository;

use Kodifikator\Entity\Kodifikator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

class KodifikatorRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kodifikator::class);
    }

    /**
     * Batch upsert (update or insert) records in chunks of 300.
     *
     * This method processes a generator of rows, groups them into batches,
     * and either updates existing entities or creates new ones based on a computed hash key.
     *
     * @param \Generator $rows Generator yielding arrays representing rows to be upserted
     * @return int Number of processed records
     */
    public function upsertBatch(\Generator $rows): int
    {
        $em = $this->getEntityManager();
        $batchSize = 300;
        $processedCount = 0;
        $hashBuffer = [];
        $hashKeys = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $hashKey = $this->computeHashKey($row);
            $hashBuffer[$hashKey] = $row;
            $hashKeys[] = $hashKey;

            if (count($hashBuffer) >= $batchSize) {
                $processedCount += $this->processBatch($hashBuffer, $hashKeys, $em);
                $hashBuffer = []; 
                $hashKeys = [];
            }
        }

        if (!empty($hashBuffer)) {
            $processedCount += $this->processBatch($hashBuffer, $hashKeys, $em);
        }

        return $processedCount;
    }
    
    /**
     * Processes a batch of rows: updates existing entities or inserts new ones.
     *
     * Retrieves existing entities by their hash keys, updates them with new data or creates new entities
     * if they do not exist, then persists and flushes changes to the database.
     *
     * @param array $batch Associative array of rows keyed by hash keys
     * @param array $hashKeys List of hash keys in the batch
     * @param EntityManagerInterface $em Doctrine entity manager
     * @return int Number of records processed in this batch
     */   
    private function processBatch(array $batch, array $hashKeys, EntityManagerInterface $em): int
    {
        $existing = $this->getExisting($hashKeys);
        $count = 0;

        foreach ($batch as $hashKey => $row) {
            $entity = $existing[$hashKey] ?? new Kodifikator();

            $entity
                ->setLevel1($row[0] ?? null)
                ->setLevel2($row[1] ?? null)
                ->setLevel3($row[2] ?? null)
                ->setLevel4($row[3] ?? null)
                ->setAddLevel($row[4] ?? null)
                ->setCategory($row[5] ?? null)
                ->setName(trim((string) ($row[6] ?? '')))
                ->setHashKey($hashKey);

            $em->persist($entity);
            $count++;
        }

        $em->flush();
        $em->clear();

        return $count;
    }

    /**
     * Retrieves existing Kodifikator entities by a list of hash keys.
     *
     * @param array $hashKeys List of hash keys to find existing entities
     * @return Kodifikator[] Associative array of entities indexed by hash key
     */
    private function getExisting(array $hashKeys)
    {
        if (empty($hashKeys)) {
            return [];
        }
        return $this->createQueryBuilder('k')
            ->where('k.hashKey IN (:keys)')
            ->setParameter('keys', $hashKeys)
            ->indexBy('k', 'k.hashKey')
            ->getQuery()
            ->getResult();
    }

    /**
     * Computes a unique hash key for a row based on specific columns.
     *
     * Combines the first five columns of the row into a string separated by colons,
     * then generates an MD5 hash from that string.
     *
     * @param array $row Array representing a single data row
     * @return string MD5 hash key representing the unique identifier for the row
     */
    private function computeHashKey(array $row): string
    {
        return md5(
            "$row[0]:$row[1]:$row[2]:$row[3]:$row[4]"
        );
    }
}

