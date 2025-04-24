<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: "App\Repository\TenderRepository")]
class Tender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(['tender'])]
    private ?int $id = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotBlank]
    #[Groups(['tender'])]
    #[SerializedName('externalCode')]
    private ?int $externalCode = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[Groups(['tender'])]
    #[SerializedName('number')]
    private ?string $number = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    #[Groups(['tender'])]
    #[SerializedName('status')]
    private ?string $status = null;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Groups(['tender'])]
    #[SerializedName('name')]
    private ?string $name = null;

    #[ORM\Column(type: "datetime")]
    #[Groups(['tender'])]
    #[SerializedName('date')]
    private ?\DateTime $date = null;

    #[Gedmo\Timestampable(on: "create")]
    #[ORM\Column(type: "datetime")]
    #[Groups(['tender'])]
    #[SerializedName('createdAt')]
    private ?\DateTime $createdAt = null;

    #[Gedmo\Timestampable(on: "update")]
    #[ORM\Column(type: "datetime")]
    #[Groups(['tender'])]
    #[SerializedName('updatedAt')]
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalCode(): ?int
    {
        return $this->externalCode;
    }

    public function setExternalCode(?int $externalCode): self
    {
        $this->externalCode = $externalCode;
        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}