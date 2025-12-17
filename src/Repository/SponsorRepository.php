<?php

namespace App\Repository;

use App\Entity\Sponsor;
use App\Enum\TypeSponsorEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /**
     * @return Sponsor[]
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche multicritère des sponsors.
     *
     * Critères possibles (tous optionnels) :
     *  - search (string) : recherche par nom, email ou numéro de contrat
     *  - minBudget (float)
     *  - maxBudget (float)
     *  - type (string, valeur de TypeSponsorEnum)
     *  - city (string)
     *  - startDate (DateTimeInterface)
     *  - endDate (DateTimeInterface)
     *
     * @param array<string, mixed> $criteria
     * @return Sponsor[]
     */
    public function searchByCriteria(array $criteria): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.sponsorships', 'sp')
            ->leftJoin('s.sponsorContracts', 'c')
            ->addSelect('sp', 'c')
            ->groupBy('s.id');

        // Recherche textuelle (nom, email ou numéro de contrat)
        if (!empty($criteria['search'])) {
            $searchTerm = '%' . $criteria['search'] . '%';
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('s.name', ':search'),
                    $qb->expr()->like('s.email', ':search'),
                    $qb->expr()->like('c.contractNumber', ':search')
                )
            )->setParameter('search', $searchTerm);
        }

        // Type de sponsor
        if (!empty($criteria['type'])) {
            // accepte soit une string (ENTREPRISE, ...) soit directement l'Enum
            $type = $criteria['type'] instanceof TypeSponsorEnum
                ? $criteria['type']
                : TypeSponsorEnum::from($criteria['type']);

            $qb->andWhere('s.type = :type')
               ->setParameter('type', $type);
        }

        // Ville (LIKE)
        if (!empty($criteria['city'])) {
            $qb->andWhere('s.city LIKE :city')
               ->setParameter('city', '%'.$criteria['city'].'%');
        }

        // Budget min / max (somme des montants de Sponsorship)
        if (!empty($criteria['minBudget'])) {
            $qb->andHaving('COALESCE(SUM(sp.amount), 0) >= :minBudget')
               ->setParameter('minBudget', (float) $criteria['minBudget']);
        }

        if (!empty($criteria['maxBudget'])) {
            $qb->andHaving('COALESCE(SUM(sp.amount), 0) <= :maxBudget')
               ->setParameter('maxBudget', (float) $criteria['maxBudget']);
        }

        // Date de début de contrat (signedAt)
        if (!empty($criteria['startDate'])) {
            $qb->andWhere('c.signedAt >= :startDate')
               ->setParameter('startDate', $criteria['startDate']);
        }

        // Date de fin de contrat (expiresAt)
        if (!empty($criteria['endDate'])) {
            $qb->andWhere('c.expiresAt <= :endDate')
               ->setParameter('endDate', $criteria['endDate']);
        }

        $qb->orderBy('s.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}


