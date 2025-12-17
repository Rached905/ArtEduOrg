<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(EventRepository $eventRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupérer les événements à venir et en cours
        $now = new \DateTimeImmutable();
        $allEvents = $eventRepository->findAll();
        
        $upcomingEvents = [];
        $ongoingEvents = [];
        
        foreach ($allEvents as $event) {
            $startDate = $event->getStartDate() ? new \DateTimeImmutable($event->getStartDate()->format('Y-m-d H:i:s')) : null;
            $endDate = $event->getEndDate() ? new \DateTimeImmutable($event->getEndDate()->format('Y-m-d H:i:s')) : null;
            
            if ($startDate && $startDate > $now) {
                $upcomingEvents[] = $event;
            } elseif ($endDate && $endDate >= $now) {
                $ongoingEvents[] = $event;
            }
        }

        return $this->render('client/index.html.twig', [
            'user' => $user,
            'upcoming_events' => array_slice($upcomingEvents, 0, 6),
            'ongoing_events' => array_slice($ongoingEvents, 0, 6),
        ]);
    }

    #[Route('/client/evenements', name: 'app_client_evenements')]
    public function evenements(EventRepository $eventRepository): Response
    {
        $now = new \DateTimeImmutable();
        $allEvents = $eventRepository->findAll();
        
        $upcomingEvents = [];
        $ongoingEvents = [];
        $pastEvents = [];
        
        foreach ($allEvents as $event) {
            $startDate = $event->getStartDate() ? new \DateTimeImmutable($event->getStartDate()->format('Y-m-d H:i:s')) : null;
            $endDate = $event->getEndDate() ? new \DateTimeImmutable($event->getEndDate()->format('Y-m-d H:i:s')) : null;
            
            if ($startDate && $startDate > $now) {
                $upcomingEvents[] = $event;
            } elseif ($endDate && $endDate >= $now) {
                $ongoingEvents[] = $event;
            } else {
                $pastEvents[] = $event;
            }
        }

        return $this->render('client/evenements.html.twig', [
            'upcoming_events' => $upcomingEvents,
            'ongoing_events' => $ongoingEvents,
            'past_events' => $pastEvents,
        ]);
    }

    #[Route('/client/mes-achats', name: 'app_client_mes_achats')]
    public function mesAchats(TicketRepository $ticketRepository): Response
    {
        // Vérifier que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $user = $this->getUser();
        
        // Récupérer les tickets de l'utilisateur par email
        $tickets = [];
        if ($user && $user->getEmail()) {
            $tickets = $ticketRepository->findBy(
                ['buyerEmail' => $user->getEmail()],
                ['issuedAt' => 'DESC']
            );
        }

        return $this->render('client/mes_achats.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/client/mes-favoris', name: 'app_client_favoris')]
    public function mesFavoris(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('client/favoris.html.twig');
    }
}