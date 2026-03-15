<?php

namespace App\Controller;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Entity\Ticket;
use App\Form\TicketType;

use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ticket')]
final class TicketController extends AbstractController
{
    #[Route('/stats', name: 'app_sales_stats', methods: ['GET'])]
    public function stats(TicketRepository $ticketRepository): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $now = new \DateTimeImmutable();
        $allTickets = $ticketRepository->findAll();
        
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

        // Tickets vendus cette semaine
        $startOfWeek = $now->modify('monday this week')->setTime(0, 0, 0);
        $ticketsThisWeek = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfWeek')
            ->setParameter('startOfWeek', $startOfWeek)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Tickets vendus ce mois
        $startOfMonth = $now->modify('first day of this month')->setTime(0, 0, 0);
        $ticketsThisMonth = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Revenus totaux
        $totalRevenue = $ticketRepository->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Revenus aujourd'hui
        $revenueToday = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfDay')
            ->andWhere('t.issuedAt <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Revenus cette semaine
        $revenueThisWeek = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfWeek')
            ->setParameter('startOfWeek', $startOfWeek)
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Revenus ce mois
        $revenueThisMonth = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfMonth')
            ->setParameter('startOfMonth', $startOfMonth)
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Statistiques par événement
        $eventsStats = [];
        $eventsMap = [];
        foreach ($allTickets as $ticket) {
            $event = $ticket->getEvent();
            if ($event) {
                $eventId = $event->getId();
                if (!isset($eventsMap[$eventId])) {
                    $eventsMap[$eventId] = [
                        'event' => $event,
                        'tickets_count' => 0,
                        'revenue' => 0,
                    ];
                }
                $eventsMap[$eventId]['tickets_count']++;
                $eventsMap[$eventId]['revenue'] += ($ticket->getPrice() ?? 0) * ($ticket->getQuantity() ?? 1);
            }
        }
        $eventsStats = array_values($eventsMap);
        usort($eventsStats, fn($a, $b) => $b['revenue'] - $a['revenue']);
        $topEvents = array_slice($eventsStats, 0, 5);

        // Derniers tickets vendus
        $recentTickets = $ticketRepository->findBy([], ['issuedAt' => 'DESC'], 10);

        return $this->render('ticket/stats.html.twig', [
            'total_tickets' => count($allTickets),
            'tickets_today' => $ticketsToday,
            'tickets_this_week' => $ticketsThisWeek,
            'tickets_this_month' => $ticketsThisMonth,
            'total_revenue' => $totalRevenue,
            'revenue_today' => $revenueToday,
            'revenue_this_week' => $revenueThisWeek,
            'revenue_this_month' => $revenueThisMonth,
            'top_events' => $topEvents,
            'recent_tickets' => $recentTickets,
            'now' => $now,
        ]);
    }

    #[Route(name: 'app_ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $tickets = $ticketRepository->findAll();
        $now = new \DateTimeImmutable();

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

        // Revenus totaux
        $totalRevenue = $ticketRepository->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Revenus aujourd'hui
        $revenueToday = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfDay')
            ->andWhere('t.issuedAt <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
            'tickets_today' => $ticketsToday,
            'total_revenue' => $totalRevenue,
            'revenue_today' => $revenueToday,
        ]);
    }

    #[Route('/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/scan/{token}', name: 'app_ticket_scan', methods: ['GET'])]
    public function scan(string $token, TicketRepository $ticketRepository): Response
    {
        $ticket = $ticketRepository->findOneBy(['uniqueToken' => $token]);

        if (!$ticket) {
            throw $this->createNotFoundException('Ticket non trouvé.');
        }

        // Utiliser APP_URL si défini, sinon fallback sur la détection automatique
        $baseUrl = $_ENV['APP_URL'] ?? null;
        if ($baseUrl) {
            $scanUrl = rtrim($baseUrl, '/') . $this->generateUrl('app_ticket_scan', ['token' => $ticket->getUniqueToken()]);
        } else {
            $scanUrl = $this->generateUrl('app_ticket_scan', ['token' => $ticket->getUniqueToken()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        // Générer le QR code pour l'affichage
        $writer = new SvgWriter();
        $qrCode = new QrCode($scanUrl);

        $result = $writer->write($qrCode);
        $qrSvg = $result->getString();

        // Déterminer le template selon le rôle de l'utilisateur connecté
        $user = $this->getUser();
        $template = 'ticket/show.html.twig';
        if ($user && $user->getRole() && in_array($user->getRole()->value, ['CLIENT', 'AGENT'])) {
            $template = 'ticket/show_client.html.twig';
        }
        
        return $this->render($template, [
            'ticket' => $ticket,
            'qrSvg' => $qrSvg,
            'qrCodeTargetUrl' => $scanUrl,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        // Vérifier que l'utilisateur est admin, vendeur, ou propriétaire du ticket
        $user = $this->getUser();
        $isOwner = $user && $user->getEmail() === $ticket->getBuyerEmail();
        $isVendor = $this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR');
        
        if (!$isOwner && !$this->isGranted('ROLE_ADMIN') && !$isVendor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce ticket.');
        }
        
        // ⚠️ Replace ngrok with APP_URL when possible
        $publicBaseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';

        // Generate relative path
        $path = $this->generateUrl('app_check_in', [
            'token' => $ticket->getUniqueToken(),
        ]);

        // Full URL embedded in QR
        $qrCodeTargetUrl = rtrim($publicBaseUrl, '/') . $path;

        // ✅ Old Endroid-compatible QR creation
        $qrCode = new QrCode($qrCodeTargetUrl);
        
        $writer = new SvgWriter();
        $qrSvg = $writer->write($qrCode)->getString();

        // Déterminer le template selon le rôle
        $template = 'ticket/show.html.twig';
        if ($user && $user->getRole() && in_array($user->getRole()->value, ['CLIENT', 'AGENT'])) {
            $template = 'ticket/show_client.html.twig';
        } elseif ($isVendor || ($this->isGranted('ROLE_VENDOR') || $this->isGranted('ROLE_VENDEUR'))) {
            $template = 'ticket/show_vendor.html.twig';
        }
        
        return $this->render($template, [
            'ticket' => $ticket,
            'qrSvg' => $qrSvg,
            'qrCodeTargetUrl' => $qrCodeTargetUrl,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_ticket_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $ticket->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/purchase', name: 'app_ticket_purchase', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function purchase(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $buyerName = trim($request->request->get('buyer_name', ''));
        $buyerEmail = trim($request->request->get('buyer_email', ''));
        $quantity = (int) $request->request->get('quantity', 1);

        // Règle 2 : Vérification du statut de l'événement
        $status = $event->getStatus();
        if ($status === EventStatus::TERMINE || $status === EventStatus::ANNULE) {
            $this->addFlash('error', 'L\'événement est terminé ou annulé. Les achats sont fermés.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        // Règle 1 : Limite de quantité
        if ($quantity > 10) {
            $this->addFlash('error', 'Vous ne pouvez pas acheter plus de 10 billets à la fois.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        // Validation des données
        $errors = [];
        if (empty($buyerName)) {
            $errors[] = 'Le nom de l\'acheteur est requis.';
        }

        if (empty($buyerEmail) || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Une adresse email valide est requise.';
        }
        if ($quantity <= 0) {
            $errors[] = 'Le nombre de places doit être supérieur à zéro.';
        }

        // Vérification de la capacité
        $currentSold = $event->getTicketsSold();
        $availableSeats = $event->getCapacity() - $currentSold;

        if ($quantity <= 0) {
            $errors[] = 'Le nombre de billets doit être supérieur à zéro.';
        } elseif ($quantity > $availableSeats) {
            $errors[] = sprintf('Désolé, il ne reste que %d place(s) disponible(s) pour cet événement.', $availableSeats);
        }

        // Si des erreurs, on redirige avec les messages d'erreur
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        // Création des billets dans une transaction
        $entityManager->beginTransaction();

        try {
            // Création des billets
            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->setEvent($event);
                $ticket->setBuyerName($buyerName);
                $ticket->setBuyerEmail($buyerEmail);
                $ticket->setPrice($event->getPrice() ? (float) $event->getPrice() : 0.0);
                $ticket->setQuantity(1); // Chaque billet est pour 1 personne
                $ticket->setSeatNumber($currentSold + $i + 1); // Numéro de siège incrémenté
                $ticket->setIssuedAt(new \DateTimeImmutable());
                $entityManager->persist($ticket);
            }

            // Mise à jour du nombre de billets vendus
            $event->setTicketsSold($currentSold + $quantity);
            $entityManager->flush();
            $entityManager->commit();

            // Redirection vers la page "Mes Achats" du client après achat
            $this->addFlash('success', sprintf(
                '%d billet(s) acheté(s) avec succès ! Vous pouvez les consulter dans "Mes Achats".',
                $quantity
            ));
            
            // Vérifier si l'utilisateur est connecté et est un client (AGENT = CLIENT)
            $user = $this->getUser();
            if ($user && method_exists($user, 'getRole') && $user->getRole()) {
                $roleValue = $user->getRole()->value;
                // AGENT = CLIENT dans le système
                if ($roleValue === 'CLIENT' || $roleValue === 'AGENT') {
                    return $this->redirectToRoute('app_client_mes_achats');
                }
            }
            
            // Sinon, rediriger vers la page de l'événement
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);

        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue lors de l\'achat des billets. Veuillez réessayer.');
        }

        // En cas d'erreur, redirection vers la page de l'événement
        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }
}


