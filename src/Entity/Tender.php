<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: "App\Repository\TenderRepository")]
class Tender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: "/^[\p{L}0-9\s\-_!.,()\[\]\/&]+$/u", message: "Name can only contain letters, numbers, spaces, hyphens, underscores, and some special characters (!.,()[]/&).")]
    private ?string $name = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTime $date = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank]
    private ?int $externalCode = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    private ?string $number = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getExternalCode(): ?int
    {
        return $this->externalCode;
    }

    public function setExternalCode(int $externalCode): self
    {
        $this->externalCode = $externalCode;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}