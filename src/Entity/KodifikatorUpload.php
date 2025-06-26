<?php

namespace Kodifikator\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class KodifikatorUpload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private int $id;

    #[ORM\Column(type:"string", length:255)]
    private string $date;

    #[ORM\Column(type:"string", length:255)]
    private string $pdfUrl;

    #[ORM\Column(type:"string", length:255, unique:true)]
    private string $xlsxUrl;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private ?string $docxUrl = null;

    #[ORM\Column(type:"text", nullable:true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 20, options: ["default" => "new"])]
    private string $status = 'new';

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPdfUrl(): ?string
    {
        return $this->pdfUrl;
    }

    public function setPdfUrl(string $pdfUrl): self
    {
        $this->pdfUrl = $pdfUrl;
        return $this;
    }

    public function getXlsxUrl(): ?string
    {
        return $this->xlsxUrl;
    }

    public function setXlsxUrl(string $xlsxUrl): self
    {
        $this->xlsxUrl = $xlsxUrl;
        return $this;
    }

    public function getDocxUrl(): ?string
    {
        return $this->docxUrl;
    }

    public function setDocxUrl(?string $docxUrl): self
    {
        $this->docxUrl = $docxUrl;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
