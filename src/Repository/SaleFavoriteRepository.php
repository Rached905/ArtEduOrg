<?php

namespace App\Repository;

use App\Entity\Sale;
use App\Entity\SaleFavorite;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaleFavorite>
 */
class SaleFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleFavorite::class);
    }

    /**
     * @return Sale[]
     */
    public function findSalesByUser(Users $user): array
    {
        $favorites = $this->createQueryBuilder('f')
            ->innerJoin('f.sale', 's')
            ->addSelect('s')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(fn (SaleFavorite $f) => $f->getSale(), $favorites);
    }

    public function isFavorite(Users $user, Sale $sale): bool
    {
        return $this->createQueryBuilder('f')
            ->select('1')
            ->andWhere('f.user = :user')
            ->andWhere('f.sale = :sale')
            ->setParameter('user', $user)
            ->setParameter('sale', $sale)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function findOneByUserAndSale(Users $user, Sale $sale): ?SaleFavorite
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.sale = :sale')
            ->setParameter('user', $user)
            ->setParameter('sale', $sale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByUser(Users $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
