<?php

namespace App\Repository;

use App\Entity\SponsorContract;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SponsorContract>
 */
class SponsorContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorContract::class);
    }

    /**
     * Retourne les contrats qui expirent dans les $days prochains jours.
     * 
     * Cette méthode recherche les contrats dont la date d'expiration est :
     * - Supérieure ou égale à aujourd'hui (pas encore expirés)
     * - Inférieure ou égale à aujourd'hui + $days jours
     *
     * @param int $days Nombre de jours avant expiration
     * @return SponsorContract[]
     */
    public function findExpiringWithinDays(int $days): array
    {
        // Date de début : aujourd'hui à 00:00:00 (utiliser DateTime pour correspondre à l'entité)
        $now = new \DateTime('today');
        // Date de fin : aujourd'hui + $days jours à 23:59:59
        $limit = (new \DateTime('today'))->modify("+$days days")->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->leftJoin('c.sponsor', 's')
            ->addSelect('s')
            ->andWhere('c.expiresAt >= :now')
            ->andWhere('c.expiresAt <= :limit')
            ->setParameter('now', $now)
            ->setParameter('limit', $limit)
            ->orderBy('c.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SponsorContract[]
     */
    public function findAllWithSponsor(): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sc.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return SponsorContract[]
     */
    public function findRecentWithSponsor(int $limit = 5): array
    {
        return $this->createQueryBuilder('sc')
            ->leftJoin('sc.sponsor', 's')
            ->addSelect('s')
            ->orderBy('sc.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}


