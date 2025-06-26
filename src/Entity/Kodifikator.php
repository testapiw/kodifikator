<?php

namespace Kodifikator\Entity;

/**
 * php bin/console make:migration
 * php bin/console doctrine:migrations:migrate
 */
use Doctrine\ORM\Mapping as ORM;
use Kodifikator\Repository\KodifikatorRepository;

#[ORM\Entity(repositoryClass: KodifikatorRepository::class)]
class Kodifikator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private ?int $id = null;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private ?string $level1 = null;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private ?string $level2 = null;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private ?string $level3 = null;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private ?string $level4 = null;

    #[ORM\Column(type:"string", length:20, nullable:true)]
    private ?string $addLevel = null;

    #[ORM\Column(type:"string", length:2, nullable:true)]
    private ?string $category = null;

    #[ORM\Column(type:"string", length:255)]
    private string $name;

    #[ORM\Column(type: "string", length: 32, unique: true)]
    private string $hashKey;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel1(): ?string
    {
        return $this->level1;
    }

    public function setLevel1(?string $level1): self
    {
        $this->level1 = $level1;
        return $this;
    }

    public function getLevel2(): ?string
    {
        return $this->level2;
    }

    public function setLevel2(?string $level2): self
    {
        $this->level2 = $level2;
        return $this;
    }

    public function getLevel3(): ?string
    {
        return $this->level3;
    }

    public function setLevel3(?string $level3): self
    {
        $this->level3 = $level3;
        return $this;
    }

    public function getLevel4(): ?string
    {
        return $this->level4;
    }

    public function setLevel4(?string $level4): self
    {
        $this->level4 = $level4;
        return $this;
    }

    public function getAddLevel(): ?string
    {
        return $this->addLevel;
    }

    public function setAddLevel(?string $addLevel): self
    {
        $this->addLevel = $addLevel;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getHashKey(): string
    {
        return $this->hashKey;
    }

    public function setHashKey(string $hashKey): self
    {
        $this->hashKey = $hashKey;
        return $this;
    }

    private function computeHashKey(array $data): string
    {
        $parts = [
            $data['level1'] ?? '',
            $data['level2'] ?? '',
            $data['level3'] ?? '',
            $data['level4'] ?? '',
            $data['addLevel'] ?? '',
        ];
        return md5(implode('|', $parts));
    }
}


/*
// php bin/console doctrine:mapping:info
//  php bin/console doctrine:migrations:diff
// php bin/console doctrine:migrations:migrate

// php bin/console make:repository Kodifikator

// composer require doctrine/doctrine-migrations-bundle

/*
    // Получаем репозиторий через контейнер, например в сервисе или контроллере
    $kodifikatorRepository = $entityManager->getRepository(Kodifikator::class);

    $repo = $entityManager->getRepository(Kodifikator::class);
    / ** @var \Kodifikator\Repository\KodifikatorRepository $repo * /
   $repo->upsertBatch($batchArrayOfKodifikatorData);

    // Данные в формате массива (пример)
    $data = [
        [
            'level1' => 'UA01000000000013043',
            'level2' => 'UA01020000000022387',
            'level3' => null,
            'level4' => null,
            'addLevel' => null,
            'category' => 'O',
            'name' => 'Автономна Республіка Крим',
        ],
        // ... до 300 и более записей
    ];

    $kodifikatorRepository->upsertBulk($data);


 <?php

namespace App\Utility\Repository;

use App\Utility\Entity\Kodifikator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KodifikatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kodifikator::class);
    }

    /**
     * Обновляет или создаёт записи в базе.
     * Принимает массив данных, где каждый элемент - массив с полями для Kodifikator.
     *
     * @param array $items
     * @return void
     * /
    public function upsertBulk(array $items): void
    {
        $em = $this->getEntityManager();

        // Для ускорения и экономии памяти, пакетная обработка по 100-300 штук
        $batchSize = 100;
        $i = 0;

        foreach ($items as $data) {
            // По какому полю искать? Предположим, по уникальному сочетанию уровней
            // Например, по комбинации level1 + level2 + ... (или другому уникальному индексу)
            $existing = $this->findOneBy([
                'level1' => $data['level1'] ?? null,
                'level2' => $data['level2'] ?? null,
                'level3' => $data['level3'] ?? null,
                'level4' => $data['level4'] ?? null,
                'addLevel' => $data['addLevel'] ?? null,
            ]);

            if (!$existing) {
                $kodifikator = new Kodifikator();
            } else {
                $kodifikator = $existing;
            }

            // Заполняем поля
            $kodifikator->setLevel1($data['level1'] ?? null);
            $kodifikator->setLevel2($data['level2'] ?? null);
            $kodifikator->setLevel3($data['level3'] ?? null);
            $kodifikator->setLevel4($data['level4'] ?? null);
            $kodifikator->setAddLevel($data['addLevel'] ?? null);
            $kodifikator->setCategory($data['category'] ?? null);
            $kodifikator->setName($data['name'] ?? '');

            $em->persist($kodifikator);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            $i++;
        }

        // Финальный flush
        $em->flush();
        $em->clear();
    }
}
 */