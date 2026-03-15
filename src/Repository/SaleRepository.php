<?php

namespace App\Repository;

use App\Entity\Sale;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    /**
     * Récupère les ventes avec le statut "en attente" uniquement
     * Gère les statuts invalides en base de données
     * 
     * @return Sale[]
     */
    public function findPendingSales(): array
    {
        // Utiliser une requête SQL brute pour récupérer les IDs des ventes "en attente"
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id FROM sale WHERE status = 'en attente' ORDER BY created_at DESC";
        $ids = $conn->fetchFirstColumn($sql);
        
        if (empty($ids)) {
            return [];
        }
        
        // Charger les entités une par une en gérant les exceptions
        $sales = [];
        foreach ($ids as $id) {
            try {
                $sale = $this->find($id);
                if ($sale && $sale->getStatus() && $sale->getStatus()->value === 'en attente') {
                    $sales[] = $sale;
                }
            } catch (\Exception $e) {
                // Ignorer les ventes avec des statuts invalides
                continue;
            }
        }
        
        return $sales;
    }

    /**
     * Récupère toutes les ventes triées : "en attente" en premier, puis par date de création (plus récent en premier)
     * Toutes les ventes "en attente" (de tous les utilisateurs) s'affichent en premier
     * Gère les statuts invalides en base de données
     * 
     * @return Sale[]
     */
    public function findAllOrderedByStatus(): array
    {
        // Utiliser une requête SQL brute pour récupérer tous les IDs
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT id FROM sale ORDER BY created_at DESC";
        $ids = $conn->fetchFirstColumn($sql);
        
        if (empty($ids)) {
            return [];
        }
        
        // Charger les entités une par une en gérant les exceptions
        $allSales = [];
        foreach ($ids as $id) {
            try {
                $sale = $this->find($id);
                if ($sale && $sale->getStatus()) {
                    $allSales[] = $sale;
                }
            } catch (\Exception $e) {
                // Ignorer les ventes avec des statuts invalides
                continue;
            }
        }
        
        // Séparer les ventes "en attente" des autres
        $enAttente = [];
        $autres = [];
        
        foreach ($allSales as $sale) {
            try {
            if ($sale->getStatus() && $sale->getStatus()->value === 'en attente') {
                $enAttente[] = $sale;
            } else {
                $autres[] = $sale;
                }
            } catch (\Exception $e) {
                // Ignorer les ventes avec des statuts invalides
                continue;
            }
        }
        
        // Trier chaque groupe par date de création (plus récent en premier)
        usort($enAttente, function($a, $b) {
            $aDate = $a->getCreatedAt() ?? new \DateTime('1970-01-01');
            $bDate = $b->getCreatedAt() ?? new \DateTime('1970-01-01');
            return $bDate <=> $aDate;
        });
        
        usort($autres, function($a, $b) {
            $aDate = $a->getCreatedAt() ?? new \DateTime('1970-01-01');
            $bDate = $b->getCreatedAt() ?? new \DateTime('1970-01-01');
            return $bDate <=> $aDate;
        });
        
        // Combiner : "en attente" en premier, puis les autres
        return array_merge($enAttente, $autres);
    }

    /**
     * Récupère toutes les ventes actives triées : disponibles en premier, puis les autres
     * Exclut les ventes vendues, payées ou inactives
     * 
     * @return Sale[]
     */
    public function findAllAvailableFirst(): array
    {
        $allSales = $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :isActive')
            ->andWhere('s.status NOT IN (:soldStatuses)')
            ->setParameter('isActive', true)
            ->setParameter('soldStatuses', ['vendue', 'paye', 'payer'])
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Séparer les ventes disponibles des autres
        $disponibles = [];
        $autres = [];

        foreach ($allSales as $sale) {
            try {
                $status = $sale->getStatus();
                if ($status && in_array($status->value, ['disponible', 'en attente'])) {
                    $disponibles[] = $sale;
                } else {
                    $autres[] = $sale;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Trier chaque groupe par date de création (plus récent en premier)
        usort($disponibles, function($a, $b) {
            $aDate = $a->getCreatedAt() ?? new \DateTime('1970-01-01');
            $bDate = $b->getCreatedAt() ?? new \DateTime('1970-01-01');
            return $bDate <=> $aDate;
        });

        usort($autres, function($a, $b) {
            $aDate = $a->getCreatedAt() ?? new \DateTime('1970-01-01');
            $bDate = $b->getCreatedAt() ?? new \DateTime('1970-01-01');
            return $bDate <=> $aDate;
        });

        // Combiner : disponibles en premier, puis les autres
        return array_merge($disponibles, $autres);
    }

    /**
     * Récupère les ventes achetées par un utilisateur spécifique
     * (par relation acheteur OU par email acheteur enregistré au paiement)
     *
     * @param Users $user L'utilisateur connecté (client)
     * @return Sale[]
     */
    public function findByAcheteur(Users $user): array
    {
        $soldStatuses = ['paye', 'vendue', 'payer'];
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.status IN (:soldStatuses)')
            ->setParameter('soldStatuses', $soldStatuses)
            ->orderBy('s.createdAt', 'DESC');

        $email = $user->getEmail() ? strtolower(trim($user->getEmail())) : null;
        if ($email !== null && $email !== '') {
            $qb->andWhere('s.acheteur = :user OR s.buyerEmail = :email')
                ->setParameter('user', $user)
                ->setParameter('email', $email);
        } else {
            $qb->andWhere('s.acheteur = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les ventes d'un vendeur spécifique, triées par date de création (plus récent en premier)
     * 
     * @param Users $vendeur Le vendeur dont on veut récupérer les ventes
     * @return Sale[]
     */
    public function findByVendeur(Users $vendeur): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.vendeur = :vendeur')
            ->setParameter('vendeur', $vendeur)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les ventes populaires d'un vendeur (limitées à un certain nombre)
     * 
     * @param Users $vendeur Le vendeur dont on veut récupérer les ventes
     * @param int $limit Nombre maximum de ventes à retourner
     * @return Sale[]
     */
    public function findPopularByVendeur(Users $vendeur, int $limit = 4): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.vendeur = :vendeur')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('vendeur', $vendeur)
            ->setParameter('isActive', true)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule les revenus du mois pour un vendeur
     * 
     * @param Users $vendeur Le vendeur
     * @return float
     */
    public function calculateMonthlyRevenue(Users $vendeur): float
    {
        $startOfMonth = new \DateTime('first day of this month');
        $startOfMonth->setTime(0, 0, 0);
        
        // Récupérer toutes les ventes du vendeur ce mois
        $sales = $this->createQueryBuilder('s')
            ->andWhere('s.vendeur = :vendeur')
            ->andWhere('s.createdAt >= :startOfMonth')
            ->setParameter('vendeur', $vendeur)
            ->setParameter('startOfMonth', $startOfMonth)
            ->getQuery()
            ->getResult();
        
        $total = 0.0;
        foreach ($sales as $sale) {
            // Compter seulement les ventes vendues ou payées
            $status = $sale->getStatus();
            if ($status && in_array($status->value, ['vendue', 'paye', 'payer'])) {
                $total += $sale->getAmount() ?? 0.0;
            }
        }
        
        return $total;
    }

    /**
     * Calcule le nombre total de vues (approximation basée sur les ventes actives)
     * Note: Si vous avez un système de tracking de vues, utilisez-le à la place
     * 
     * @param Users $vendeur Le vendeur
     * @return int
     */
    public function calculateTotalViews(Users $vendeur): int
    {
        // Pour l'instant, on utilise une approximation basée sur le nombre de ventes actives
        // Multiplié par un facteur aléatoire pour simuler des vues
        $activeSales = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.vendeur = :vendeur')
            ->andWhere('s.isActive = :isActive')
            ->setParameter('vendeur', $vendeur)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getSingleScalarResult();
        
        // Approximation: chaque vente active génère entre 50 et 200 vues
        return (int) ($activeSales * 100);
    }

    /**
     * Récupère les commandes récentes d'un vendeur (ventes vendues ou payées)
     * 
     * @param Users $vendeur Le vendeur
     * @param int $limit Nombre maximum de commandes à retourner
     * @return Sale[]
     */
    public function findRecentOrdersByVendeur(Users $vendeur, int $limit = 4): array
    {
        $sales = $this->createQueryBuilder('s')
            ->andWhere('s.vendeur = :vendeur')
            ->andWhere('s.status IN (:statuses)')
            ->setParameter('vendeur', $vendeur)
            ->setParameter('statuses', ['vendue', 'paye', 'payer', 'payement en cours'])
            ->orderBy('s.updatedAt', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $sales;
    }

    /**
     * Calcule les ventes par jour pour un vendeur sur une période donnée
     * 
     * @param Users $vendeur Le vendeur
     * @param int $days Nombre de jours (par défaut 7)
     * @return array Tableau associatif ['YYYY-MM-DD' => montant]
     */
    public function calculateSalesByDay(Users $vendeur, int $days = 7): array
    {
        $endDate = new \DateTime();
        $endDate->setTime(23, 59, 59);
        $startDate = clone $endDate;
        $startDate->modify("-{$days} days");
        $startDate->setTime(0, 0, 0);

        // Récupérer toutes les ventes vendues/payées dans la période
        $sales = $this->createQueryBuilder('s')
            ->andWhere('s.vendeur = :vendeur')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('s.updatedAt >= :startDate')
            ->andWhere('s.updatedAt <= :endDate')
            ->setParameter('vendeur', $vendeur)
            ->setParameter('statuses', ['vendue', 'paye', 'payer'])
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        // Initialiser le tableau avec des zéros pour tous les jours
        $salesByDay = [];
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $salesByDay[$dateKey] = 0.0;
            $currentDate->modify('+1 day');
        }

        // Remplir avec les données réelles
        foreach ($sales as $sale) {
            $saleDate = $sale->getUpdatedAt() ?? $sale->getCreatedAt();
            if ($saleDate) {
                $dateKey = $saleDate->format('Y-m-d');
                if (isset($salesByDay[$dateKey])) {
                    $salesByDay[$dateKey] += $sale->getAmount() ?? 0.0;
                }
            }
        }

        return $salesByDay;
    }
}

