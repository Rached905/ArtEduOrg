<?php

namespace App\Entity;

use App\Enum\TypeSponsorEnum;
use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
        message: 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|tn|fr|net|org)$/i',
        message: 'L\'email doit être valide et se terminer par .com, .tn, .fr, .net ou .org'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: 'Le téléphone doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[0-9+\-\s()]+$/',
        message: 'Le téléphone ne peut contenir que des chiffres, espaces, tirets, parenthèses et le signe +'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(enumType: TypeSponsorEnum::class)]
    #[Assert\NotBlank(message: 'Le type est obligatoire')]
    private ?TypeSponsorEnum $type = null;

    /**
     * @var Collection<int, SponsorContract>
     */
    #[ORM\OneToMany(targetEntity: SponsorContract::class, mappedBy: 'sponsor')]
    private Collection $sponsorContracts;

    /**
     * @var Collection<int, Sponsorship>
     */
    #[ORM\OneToMany(targetEntity: Sponsorship::class, mappedBy: 'sponsor')]
    private Collection $sponsorships;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $website = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'sponsors')]
    private Collection $events;

    public function __construct()
    {
        $this->sponsorContracts = new ArrayCollection();
        $this->sponsorships = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

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

    /**
     * @return Collection<int, SponsorContract>
     */
    public function getSponsorContracts(): Collection
    {
        return $this->sponsorContracts;
    }

    public function addSponsorContract(SponsorContract $sponsorContract): static
    {
        if (!$this->sponsorContracts->contains($sponsorContract)) {
            $this->sponsorContracts->add($sponsorContract);
            $sponsorContract->setSponsor($this);
        }

        return $this;
    }

    public function removeSponsorContract(SponsorContract $sponsorContract): static
    {
        if ($this->sponsorContracts->removeElement($sponsorContract)) {
            // set the owning side to null (unless already changed)
            if ($sponsorContract->getSponsor() === $this) {
                $sponsorContract->setSponsor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sponsorship>
     */
    public function getSponsorships(): Collection
    {
        return $this->sponsorships;
    }

    public function addSponsorship(Sponsorship $sponsorship): static
    {
        if (!$this->sponsorships->contains($sponsorship)) {
            $this->sponsorships->add($sponsorship);
            $sponsorship->setSponsor($this);
        }

        return $this;
    }

    public function removeSponsorship(Sponsorship $sponsorship): static
    {
        if ($this->sponsorships->removeElement($sponsorship)) {
            // set the owning side to null (unless already changed)
            if ($sponsorship->getSponsor() === $this) {
                $sponsorship->setSponsor(null);
            }
        }

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addSponsor($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeSponsor($this);
        }

        return $this;
    }
}


