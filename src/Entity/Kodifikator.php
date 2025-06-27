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