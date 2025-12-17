<?php

namespace App\Entity;

use App\Enum\SponsorLevelEnum;
use App\Repository\SponsorContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SponsorContractRepository::class)]
class SponsorContract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le numéro de contrat est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le numéro de contrat doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le numéro de contrat ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9\-_]+$/i',
        message: 'Le numéro de contrat ne peut contenir que des lettres, chiffres, tirets et underscores'
    )]
    private ?string $contractNumber = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date de signature est obligatoire')]
    private ?\DateTime $signedAt = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date d\'expiration est obligatoire')]
    #[Assert\Callback([self::class, 'validateExpiresAt'])]
    private ?\DateTime $expiresAt = null;

    #[ORM\Column(enumType: SponsorLevelEnum::class)]
    #[Assert\NotBlank(message: 'Le niveau est obligatoire')]
    private ?SponsorLevelEnum $level = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Les conditions sont obligatoires')]
    #[Assert\Length(
        min: 10,
        max: 5000,
        minMessage: 'Les conditions doivent contenir au moins {{ limit }} caractères',
        maxMessage: 'Les conditions ne peuvent pas dépasser {{ limit }} caractères'
    )]
    private ?string $terms = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorContracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sponsor $sponsor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContractNumber(): ?string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): static
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    public function getSignedAt(): ?\DateTime
    {
        return $this->signedAt;
    }

    public function setSignedAt(\DateTime $signedAt): static
    {
        $this->signedAt = $signedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getLevel(): ?SponsorLevelEnum
    {
        return $this->level;
    }

    public function setLevel(SponsorLevelEnum $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(string $terms): static
    {
        $this->terms = $terms;

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

    /**
     * Validation callback pour vérifier que la date d'expiration est après la date de signature
     */
    public static function validateExpiresAt($expiresAt, ExecutionContextInterface $context): void
    {
        $object = $context->getObject();
        
        if ($object instanceof SponsorContract && $object->getSignedAt() !== null && $expiresAt !== null) {
            if ($expiresAt <= $object->getSignedAt()) {
                $context->buildViolation('La date d\'expiration doit être postérieure à la date de signature')
                    ->atPath('expiresAt')
                    ->addViolation();
            }
        }
    }
}


