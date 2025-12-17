<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\Enum\EventStatus;
use App\Enum\Role;
use App\Repository\UsersRepository;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\SponsorRepository;
use App\Repository\SponsorContractRepository;
use App\Repository\SponsorshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        UsersRepository $usersRepository,
        EventRepository $eventRepository,
        TicketRepository $ticketRepository,
        SponsorRepository $sponsorRepository,
        SponsorContractRepository $sponsorContractRepository,
        SponsorshipRepository $sponsorshipRepository
    ): Response
    {
        // Récupérer tous les utilisateurs
        $allUsers = $usersRepository->findAll();
        
        // Statistiques générales
        $totalUsers = count($allUsers);
        $activeUsers = $totalUsers > 0 ? count(array_filter($allUsers, fn($u) => $u->isActive())) : 0;
        $inactiveUsers = $totalUsers - $activeUsers;
        
        // Utilisateurs par rôle
        $adminCount = count(array_filter($allUsers, fn($u) => $u->getRole() === Role::ADMIN));
        $vendorCount = count(array_filter($allUsers, fn($u) => $u->getRole() === Role::USER));
        $clientCount = count(array_filter($allUsers, fn($u) => $u->getRole() === Role::AGENT));
        
        // Nouveaux utilisateurs ce mois
        $firstDayOfMonth = new \DateTimeImmutable('first day of this month');
        $newUsersThisMonth = count(array_filter($allUsers, function($u) use ($firstDayOfMonth) {
            return $u->getCreatedAt() >= $firstDayOfMonth;
        }));
        
        // Calcul des tendances (comparaison avec le mois dernier)
        $firstDayOfLastMonth = new \DateTimeImmutable('first day of last month');
        $lastDayOfLastMonth = new \DateTimeImmutable('last day of last month');
        
        $newUsersLastMonth = count(array_filter($allUsers, function($u) use ($firstDayOfLastMonth, $lastDayOfLastMonth) {
            $createdAt = $u->getCreatedAt();
            return $createdAt >= $firstDayOfLastMonth && $createdAt <= $lastDayOfLastMonth;
        }));
        
        // Calcul du pourcentage de croissance
        $growthPercentage = $newUsersLastMonth > 0 
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100, 1)
            : ($newUsersThisMonth > 0 ? 100 : 0);
        
        // Utilisateurs récents (5 derniers)
        $recentUsers = $usersRepository->findBy([], ['createdAt' => 'DESC'], 5);
        
        // Activités récentes
        $recentActivities = $this->getRecentActivities($usersRepository);
        
        // Données pour le graphique (7 derniers jours)
        $chartData = $this->getChartData($usersRepository);
        
        // Statistiques des événements
        $now = new \DateTimeImmutable();
        $allEvents = $eventRepository->findAll();
        
        $upcomingEvents = count(array_filter($allEvents, function($e) use ($now) {
            return $e->getStartDate() > $now;
        }));
        
        $ongoingEvents = count(array_filter($allEvents, function($e) use ($now) {
            return $e->getStartDate() <= $now && $e->getEndDate() >= $now;
        }));
        
        // Statistiques des tickets
        $allTickets = $ticketRepository->findAll();
        $startOfDay = $now->setTime(0, 0, 0);
        $endOfDay = $now->setTime(23, 59, 59);
        
        $ticketsToday = count(array_filter($allTickets, function($t) use ($startOfDay, $endOfDay) {
            $issuedAt = $t->getIssuedAt();
            return $issuedAt >= $startOfDay && $issuedAt <= $endOfDay;
        }));
        
        $totalRevenue = array_sum(array_map(function($t) {
            return ($t->getPrice() ?? 0) * ($t->getQuantity() ?? 1);
        }, $allTickets));
        
        // Derniers événements
        $recentEvents = $eventRepository->findBy([], ['startDate' => 'DESC'], 10);
        
        // Statistiques des sponsors
        $totalSponsors = $sponsorRepository->count([]);
        $totalContracts = $sponsorContractRepository->count([]);
        $totalSponsorships = $sponsorshipRepository->count([]);
        $totalSponsoring = $totalSponsors + $totalContracts + $totalSponsorships;
        
        // Derniers sponsors, contrats et parrainages
        $recentSponsors = $sponsorRepository->findBy([], ['id' => 'DESC'], 2);
        $recentContracts = $sponsorContractRepository->findRecentWithSponsor(1);
        $recentSponsorships = $sponsorshipRepository->findRecentWithSponsor(1);
        
        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'new_users_this_month' => $newUsersThisMonth,
                'growth_percentage' => $growthPercentage,
                'admin_count' => $adminCount,
                'vendor_count' => $vendorCount,
                'client_count' => $clientCount,
                'upcoming_events' => $upcomingEvents,
                'ongoing_events' => $ongoingEvents,
                'tickets_today' => $ticketsToday,
                'total_revenue' => $totalRevenue,
                'total_sponsors' => $totalSponsors,
                'total_contracts' => $totalContracts,
                'total_sponsorships' => $totalSponsorships,
                'total_sponsoring' => $totalSponsoring,
            ],
            'recent_users' => $recentUsers,
            'recent_activities' => $recentActivities,
            'chart_data' => $chartData,
            'recent_events' => $recentEvents,
            'recent_sponsors' => $recentSponsors,
            'recent_contracts' => $recentContracts,
            'recent_sponsorships' => $recentSponsorships,
            'now' => $now,
        ]);
    }
    
    /**
     * Génère une liste d'activités récentes basée sur les utilisateurs
     */
    private function getRecentActivities(UsersRepository $usersRepository): array
    {
        $activities = [];
        
        // Récupérer les 10 derniers utilisateurs créés
        $recentUsers = $usersRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        foreach ($recentUsers as $user) {
            $activities[] = [
                'type' => 'user_created',
                'icon' => 'user-plus',
                'class' => 'success',
                'title' => 'Nouvel utilisateur inscrit',
                'description' => $user->getFullname() . ' (' . $user->getEmail() . ')',
                'time' => $this->formatTimeAgo($user->getCreatedAt()),
            ];
        }
        
        // Limiter aux 6 premières activités
        return array_slice($activities, 0, 6);
    }
    
    /**
     * Génère les données pour le graphique des 7 derniers jours
     */
    private function getChartData(UsersRepository $usersRepository): array
    {
        $labels = [];
        $data = [];
        $allUsers = $usersRepository->findAll();
        
        // Générer les 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} days");
            $labels[] = $date->format('d/m');
            
            // Compter les utilisateurs créés ce jour-là
            $startOfDay = $date->setTime(0, 0, 0);
            $endOfDay = $date->setTime(23, 59, 59);
            
            $count = count(array_filter(
                $allUsers,
                function($u) use ($startOfDay, $endOfDay) {
                    $createdAt = $u->getCreatedAt();
                    return $createdAt >= $startOfDay && $createdAt <= $endOfDay;
                }
            ));
            
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
    
    /**
     * Formate une date en "Il y a X temps"
     */
    private function formatTimeAgo(\DateTimeImmutable $date): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        
        if ($diff < 60) {
            return 'Il y a ' . $diff . ' seconde' . ($diff > 1 ? 's' : '');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return 'Il y a ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'Il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'Il y a ' . $days . ' jour' . ($days > 1 ? 's' : '');
        } else {
            return $date->format('d/m/Y');
        }
    }
}