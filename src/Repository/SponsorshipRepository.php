<?php

namespace App\Repository;

use App\Entity\Sponsorship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsorship>
 */
class SponsorshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsorship::class);
    }

    /**
     * @return Sponsorship[]
     */
    public function findAllWithSponsor(): array
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sp.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Sponsorship[]
     */
    public function findRecentWithSponsor(int $limit = 5): array
    {
        return $this->createQueryBuilder('sp')
            ->leftJoin('sp.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sp.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}


