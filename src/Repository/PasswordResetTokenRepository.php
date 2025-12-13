<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Supprimer tous les tokens expirés
     */
    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Supprimer tous les anciens tokens d'un utilisateur
     */
    public function deleteOldTokensForUser(int $userId): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouver un token valide
     */
    public function findValidToken(string $token, string $email): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->join('t.user', 'u')
            ->where('t.token = :token')
            ->andWhere('u.email = :email')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('email', $email)
            ->setParameter('now', new \DateTime())
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}