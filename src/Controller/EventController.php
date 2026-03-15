<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\EventReviewRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event')]
final class EventController extends AbstractController
{
    #[Route('/stats', name: 'app_event_stats', methods: ['GET'])]
    public function stats(EventRepository $eventRepository, EventReviewRepository $eventReviewRepository, TicketRepository $ticketRepository): Response
    {
        $now = new \DateTimeImmutable();
        $allEvents = $eventRepository->findAll();
        $allReviews = $eventReviewRepository->findAll();
        
        // Statistiques des événements par statut
        $statsByStatus = [
            'programme' => 0,
            'en_cours' => 0,
            'termine' => 0,
            'annule' => 0,
        ];
        
        // Statistiques des événements par date
        $upcoming = 0;
        $ongoing = 0;
        $past = 0;
        
        foreach ($allEvents as $event) {
            $status = $event->getStatus();
            if ($status === EventStatus::PROGRAMME) {
                $statsByStatus['programme']++;
            } elseif ($status === EventStatus::EN_COURS) {
                $statsByStatus['en_cours']++;
            } elseif ($status === EventStatus::TERMINE) {
                $statsByStatus['termine']++;
            } elseif ($status === EventStatus::ANNULE) {
                $statsByStatus['annule']++;
            }
            
            $startDate = $event->getStartDate() ? new \DateTimeImmutable($event->getStartDate()->format('Y-m-d H:i:s')) : null;
            $endDate = $event->getEndDate() ? new \DateTimeImmutable($event->getEndDate()->format('Y-m-d H:i:s')) : null;
            
            if ($startDate && $startDate > $now) {
                $upcoming++;
            } elseif ($endDate && $endDate >= $now) {
                $ongoing++;
            } else {
                $past++;
            }
        }
        
        // Statistiques des avis
        $totalReviews = count($allReviews);
        $averageRating = 0;
        if ($totalReviews > 0) {
            $totalRating = array_sum(array_map(fn($r) => $r->getRating(), $allReviews));
            $averageRating = round($totalRating / $totalReviews, 1);
        }
        
        // Événements avec le plus de tickets vendus
        $eventsWithTickets = [];
        foreach ($allEvents as $event) {
            $ticketsCount = $ticketRepository->count(['event' => $event]);
            if ($ticketsCount > 0) {
                $eventsWithTickets[] = [
                    'event' => $event,
                    'tickets_sold' => $ticketsCount,
                    'revenue' => array_sum(array_map(function($t) {
                        return ($t->getPrice() ?? 0) * ($t->getQuantity() ?? 1);
                    }, $ticketRepository->findBy(['event' => $event])))
                ];
            }
        }
        usort($eventsWithTickets, fn($a, $b) => $b['tickets_sold'] - $a['tickets_sold']);
        $topEvents = array_slice($eventsWithTickets, 0, 5);
        
        // Derniers événements créés
        $recentEvents = $eventRepository->findBy([], ['id' => 'DESC'], 5);
        
        return $this->render('event/stats.html.twig', [
            'total_events' => count($allEvents),
            'stats_by_status' => $statsByStatus,
            'upcoming' => $upcoming,
            'ongoing' => $ongoing,
            'past' => $past,
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'top_events' => $topEvents,
            'recent_events' => $recentEvents,
            'now' => $now,
        ]);
    }

    #[Route('/dashboard', name: 'app_event_dashboard', methods: ['GET'])]
    public function dashboard(EventRepository $eventRepository, TicketRepository $ticketRepository): Response
    {
        $events = $eventRepository->findAll();
        $tickets = $ticketRepository->findAll();
        $now = new \DateTimeImmutable();
        
        // Statistiques des événements
        $eventStats = [
            'total' => count($events),
            'upcoming' => 0,
            'ongoing' => 0,
            'past' => 0
        ];
        
        foreach ($events as $event) {
            $startDate = $event->getStartDate() ? new \DateTimeImmutable($event->getStartDate()->format('Y-m-d H:i:s')) : null;
            $endDate = $event->getEndDate() ? new \DateTimeImmutable($event->getEndDate()->format('Y-m-d H:i:s')) : null;
            
            if ($startDate && $startDate > $now) {
                $eventStats['upcoming']++;
            } elseif ($endDate && $endDate >= $now) {
                $eventStats['ongoing']++;
            } else {
                $eventStats['past']++;
            }
        }
        
        // Statistiques des tickets
        $ticketStats = [
            'total' => count($tickets),
            'today' => $ticketRepository->createQueryBuilder('t')
                ->where('t.issuedAt >= :start')
                ->andWhere('t.issuedAt <= :end')
                ->setParameter('start', $now->setTime(0, 0, 0))
                ->setParameter('end', $now->setTime(23, 59, 59))
                ->select('COUNT(t.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'revenue' => $ticketRepository->createQueryBuilder('t')
                ->select('COALESCE(SUM(t.price * t.quantity), 0)')
                ->getQuery()
                ->getSingleScalarResult()
        ];

        return $this->render('event/dashboard.html.twig', [
            'events' => $events,
            'tickets' => $tickets,
            'eventStats' => $eventStats,
            'ticketStats' => $ticketStats,
            'now' => $now
        ]);
    }

    #[Route('/admin', name: 'app_event_admin_index', methods: ['GET'])]
    public function adminIndex(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        
        // Calculer les statistiques
        $stats = [
            'programme' => 0,
            'en_cours' => 0,
            'termine' => 0,
            'annule' => 0,
        ];
        
        foreach ($events as $event) {
            $status = $event->getStatus();
            if ($status === EventStatus::PROGRAMME) {
                $stats['programme']++;
            } elseif ($status === EventStatus::EN_COURS) {
                $stats['en_cours']++;
            } elseif ($status === EventStatus::TERMINE) {
                $stats['termine']++;
            } elseif ($status === EventStatus::ANNULE) {
                $stats['annule']++;
            }
        }
        
        return $this->render('event/admin_index.html.twig', [
            'events' => $events,
            'stats' => $stats,
        ]);
    }

    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository, TicketRepository $ticketRepository): Response
    {
        $now = new \DateTimeImmutable();

        // Récupérer tous les événements
        $allEvents = $eventRepository->findAll();

        // Compter les événements par statut
        $upcoming = 0;
        $ongoing = 0;
        $past = 0;
        
        foreach ($allEvents as $event) {
            $startDate = $event->getStartDate() ? new \DateTimeImmutable($event->getStartDate()->format('Y-m-d H:i:s')) : null;
            $endDate = $event->getEndDate() ? new \DateTimeImmutable($event->getEndDate()->format('Y-m-d H:i:s')) : null;
            
            if ($startDate && $startDate > $now) {
                $upcoming++;
            } elseif ($endDate && $endDate >= $now) {
                $ongoing++;
            } else {
                $past++;
            }
        }

        // Statistiques de tickets
        $totalTickets = $ticketRepository->count([]);
        
        // Tickets vendus aujourd'hui
        $startOfDay = $now->setTime(0, 0, 0);
        $endOfDay = $now->setTime(23, 59, 59);
        $ticketsToday = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfDay')
            ->andWhere('t.issuedAt <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalRevenue = $ticketRepository->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Derniers tickets vendus
        $recentTickets = $ticketRepository->findBy([], ['issuedAt' => 'DESC'], 5);

        // Déterminer le template selon le rôle
        $template = 'event/index.html.twig';
        if ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'event/index_vendor.html.twig';
        }

        return $this->render($template, [
            'events'           => $allEvents,
            'upcoming_events'  => $upcoming,
            'ongoing_events'   => $ongoing,
            'past_events'      => $past,
            'total_tickets'    => $totalTickets,
            'tickets_today'    => $ticketsToday,
            'total_revenue'    => $totalRevenue,
            'recent_tickets'   => $recentTickets,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setUpdatedAt(new \DateTime());
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'événement a été créé avec succès.');

            return $this->redirectToRoute('app_event_admin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event, EventReviewRepository $eventReviewRepository): Response
    {
        $reviews = $eventReviewRepository->findBy(['event' => $event], ['createdAt' => 'DESC']);
        $averageRating = 0;
        if (count($reviews) > 0) {
            $totalRating = array_sum(array_map(fn($r) => $r->getRating(), $reviews));
            $averageRating = round($totalRating / count($reviews), 1);
        }
        
        // Déterminer le template selon le rôle
        $template = 'event/show.html.twig';
        if ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR')) {
            $template = 'event/show_vendor.html.twig';
        }
        
        return $this->render($template, [
            'event' => $event,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
        ]);
    }

    #[Route('/admin/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $event->setUpdatedAt(new \DateTime());
                $entityManager->flush();
                $this->addFlash('success', 'L\'événement a été mis à jour avec succès.');
                return $this->redirectToRoute('app_event_admin_index', [], Response::HTTP_SEE_OTHER);
            } else {
                // Afficher les erreurs de validation
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/{id}', name: 'app_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_event_admin_index', [], Response::HTTP_SEE_OTHER);
    }
}

