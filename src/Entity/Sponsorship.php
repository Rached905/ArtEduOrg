<?php

namespace App\Entity;

use App\Enum\SponsorshipTypeEnum;
use App\Enum\TypeSponsorEnum;
use App\Repository\SponsorshipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorshipRepository::class)]
class Sponsorship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sponsor $sponsor = null;

    #[ORM\Column(enumType: TypeSponsorEnum::class)]
    #[Assert\NotBlank(message: 'Le type est obligatoire')]
    private ?TypeSponsorEnum $type = null;

    #[ORM\Column(enumType: SponsorshipTypeEnum::class)]
    #[Assert\NotBlank(message: 'Le type de parrainage est obligatoire')]
    private ?SponsorshipTypeEnum $sponsorshipType = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Type(type: 'float', message: 'Le montant doit être un nombre')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    #[Assert\LessThanOrEqual(value: 9999999.99, message: 'Le montant ne peut pas dépasser {{ compared_value }}')]
    private ?float $amount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(?Sponsor $sponsor): static
    {
        $this->sponsor = $sponsor;

        return $this;
    }

    public function getType(): ?TypeSponsorEnum
    {
        return $this->type;
    }

    public function setType(TypeSponsorEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSponsorshipType(): ?SponsorshipTypeEnum
    {
        return $this->sponsorshipType;
    }

    public function setSponsorshipType(SponsorshipTypeEnum $sponsorshipType): static
    {
        $this->sponsorshipType = $sponsorshipType;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }
}


