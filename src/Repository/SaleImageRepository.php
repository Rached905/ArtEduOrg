<?php

namespace App\Repository;

use App\Entity\SaleImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SaleImage>
 */
class SaleImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleImage::class);
    }

    /**
     * Récupère toutes les images d'une vente
     * 
     * @return SaleImage[]
     */
    public function findBySale(int $saleId): array
    {
        return $this->createQueryBuilder('si')
            ->where('si.sale = :saleId')
            ->setParameter('saleId', $saleId)
            ->orderBy('si.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'image principale d'une vente
     */
    public function findPrimary(int $saleId): ?SaleImage
    {
        return $this->createQueryBuilder('si')
            ->where('si.sale = :saleId')
            ->andWhere('si.isPrimary = :isPrimary')
            ->setParameter('saleId', $saleId)
            ->setParameter('isPrimary', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère les images triées par sortOrder
     * 
     * @return SaleImage[]
     */
    public function findBySortOrder(int $saleId): array
    {
        return $this->createQueryBuilder('si')
            ->where('si.sale = :saleId')
            ->setParameter('saleId', $saleId)
            ->orderBy('si.sortOrder', 'ASC')
            ->addOrderBy('si.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

