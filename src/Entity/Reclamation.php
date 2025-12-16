<?php

namespace App\Entity;

use App\Repository\ReclamationRepository;
use App\Enum\StatusReclamation;
use App\Enum\TypeReclamation;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReclamationRepository::class)]
class Reclamation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $objet = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: "string", enumType: StatusReclamation::class)]
    private StatusReclamation $statusReclamation = StatusReclamation::EN_ATTENTE;

    #[ORM\Column(type: "string", enumType: TypeReclamation::class)]
    private ?TypeReclamation $typeReclamation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reponseAdmin = null;

    #[ORM\Column]
    private ?\DateTime $dateTime = null;

    #[ORM\Column]
    private ?int $user_id = 1;

    // ------------------- GETTERS / SETTERS -------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(string $objet): static
    {
        $this->objet = $objet;
        return $this;
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

    public function getStatusReclamation(): StatusReclamation
    {
        return $this->statusReclamation;
    }

    public function setStatusReclamation(StatusReclamation $statusReclamation): static
    {
        $this->statusReclamation = $statusReclamation;
        return $this;
    }

    public function getTypeReclamation(): ?TypeReclamation
    {
        return $this->typeReclamation;
    }

    public function setTypeReclamation(TypeReclamation $typeReclamation): static
    {
        $this->typeReclamation = $typeReclamation;
        return $this;
    }

    public function getReponseAdmin(): ?string
    {
        return $this->reponseAdmin;
    }

    public function setReponseAdmin(?string $reponseAdmin): static
    {
        $this->reponseAdmin = $reponseAdmin;
        return $this;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTime $dateTime): static
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;
        return $this;
    }
}