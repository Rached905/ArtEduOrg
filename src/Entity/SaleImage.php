<?php

namespace App\Entity;

use App\Repository\SaleImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SaleImageRepository::class)]
class SaleImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sale::class, inversedBy: 'saleImages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Sale $sale = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le chemin de l\'image est obligatoire.')]
    private ?string $path = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'L\'ordre de tri doit être supérieur ou égal à 0.')]
    private ?int $sortOrder = 0;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isPrimary = false;

    public function __construct()
    {
        $this->sortOrder = 0;
        $this->isPrimary = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSale(): ?Sale
    {
        return $this->sale;
    }

    public function setSale(?Sale $sale): static
    {
        $this->sale = $sale;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function isPrimary(): ?bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }
}

