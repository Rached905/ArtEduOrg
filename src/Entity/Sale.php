<?php

namespace App\Entity;

use App\Enum\SaleType;
use App\Enum\SaleStatus;
use App\Repository\SaleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SaleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Sale
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SaleType::class)]
    #[Assert\NotNull(message: 'Le type de vente est obligatoire.')]
    private ?SaleType $type = null;

    #[ORM\Column(enumType: SaleStatus::class)]
    #[Assert\NotNull(message: 'Le statut est obligatoire.')]
    private ?SaleStatus $status = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(type: 'float', message: 'Le montant doit être un nombre.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le montant doit être supérieur ou égal à 0.')]
    private ?float $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isActive = true;

    /**
     * Relation avec le vendeur (utilisateur)
     */
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Users $vendeur = null;

    /**
     * Relation avec l'acheteur (utilisateur)
     */
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Users $acheteur = null;

    /**
     * Email de l'acheteur au moment du paiement (pour afficher dans Mes achats si compte créé après)
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $buyerEmail = null;

    /**
     * @var Collection<int, SaleItem>
     */
    #[ORM\OneToMany(targetEntity: SaleItem::class, mappedBy: 'sale', orphanRemoval: true)]
    private Collection $saleItems;


    /**
     * @var Collection<int, ExchaneItem>
     */
    #[ORM\OneToMany(targetEntity: ExchaneItem::class, mappedBy: 'sale', orphanRemoval: true)]
    private Collection $exchaneItems;

    /**
     * @var Collection<int, SaleImage>
     */
    #[ORM\OneToMany(targetEntity: SaleImage::class, mappedBy: 'sale', orphanRemoval: true, cascade: ['persist'])]
    private Collection $saleImages;

    public function __construct()
    {
        $this->saleItems = new ArrayCollection();
        $this->exchangeItems = new ArrayCollection();
        $this->exchaneItems = new ArrayCollection();
        $this->saleImages = new ArrayCollection();
        $this->status = SaleStatus::EN_ATTENTE;
        $this->isActive = true;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        if ($this->status === null) {
            $this->status = SaleStatus::EN_ATTENTE;
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?SaleType
    {
        return $this->type;
    }

    public function setType(SaleType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?SaleStatus
    {
        return $this->status;
    }

    public function setStatus(SaleStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, SaleItem>
     */
    public function getSaleItems(): Collection
    {
        return $this->saleItems;
    }

    public function addSaleItem(SaleItem $saleItem): static
    {
        if (!$this->saleItems->contains($saleItem)) {
            $this->saleItems->add($saleItem);
            $saleItem->setSale($this);
        }

        return $this;
    }

    public function removeSaleItem(SaleItem $saleItem): static
    {
        if ($this->saleItems->removeElement($saleItem)) {
            // set the owning side to null (unless already changed)
            if ($saleItem->getSale() === $this) {
                $saleItem->setSale(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExchaneItem>
     */
    public function getExchaneItems(): Collection
    {
        return $this->exchaneItems;
    }

    public function addExchaneItem(ExchaneItem $exchaneItem): static
    {
        if (!$this->exchaneItems->contains($exchaneItem)) {
            $this->exchaneItems->add($exchaneItem);
            $exchaneItem->setSale($this);
        }

        return $this;
    }

    public function removeExchaneItem(ExchaneItem $exchaneItem): static
    {
        if ($this->exchaneItems->removeElement($exchaneItem)) {
            // set the owning side to null (unless already changed)
            if ($exchaneItem->getSale() === $this) {
                $exchaneItem->setSale(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContactInfo(): ?string
    {
        return $this->contactInfo;
    }

    public function setContactInfo(?string $contactInfo): static
    {
        $this->contactInfo = $contactInfo;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, SaleImage>
     */
    public function getSaleImages(): Collection
    {
        return $this->saleImages;
    }

    public function addSaleImage(SaleImage $saleImage): static
    {
        if (!$this->saleImages->contains($saleImage)) {
            $this->saleImages->add($saleImage);
            $saleImage->setSale($this);
        }

        return $this;
    }

    public function removeSaleImage(SaleImage $saleImage): static
    {
        if ($this->saleImages->removeElement($saleImage)) {
            if ($saleImage->getSale() === $this) {
                $saleImage->setSale(null);
            }
        }

        return $this;
    }

    /**
     * Récupère l'image principale de la galerie
     */
    public function getPrimaryImage(): ?SaleImage
    {
        foreach ($this->saleImages as $image) {
            if ($image->isPrimary()) {
                return $image;
            }
        }
        return $this->saleImages->first() ?: null;
    }

    /**
     * Récupère toutes les images triées par sortOrder
     */
    public function getSortedImages(): array
    {
        $images = $this->saleImages->toArray();
        usort($images, function($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });
        return $images;
    }

    /**
     * Met à jour le statut selon le workflow
     * Note: Cette méthode est simplifiée car les offres ont été supprimées
     */
    public function updateStatusFromWorkflow(): void
    {
        // La logique de mise à jour du statut basée sur les offres a été retirée
        // Le statut est maintenant géré directement par les autres fonctionnalités (achat direct, etc.)
    }

    public function getVendeur(): ?Users
    {
        return $this->vendeur;
    }

    public function setVendeur(?Users $vendeur): static
    {
        $this->vendeur = $vendeur;

        return $this;
    }

    public function getAcheteur(): ?Users
    {
        return $this->acheteur;
    }

    public function setAcheteur(?Users $acheteur): static
    {
        $this->acheteur = $acheteur;
        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyerEmail;
    }

    public function setBuyerEmail(?string $buyerEmail): static
    {
        $this->buyerEmail = $buyerEmail ? strtolower(trim($buyerEmail)) : null;

        return $this;
    }
}

