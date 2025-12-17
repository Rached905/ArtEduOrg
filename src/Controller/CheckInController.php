<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Enum\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/check-in')]
class CheckInController extends AbstractController
{
    #[Route('/{token}', name: 'app_check_in', methods: ['GET'])]
    public function checkIn(string $token, EntityManagerInterface $entityManager): Response
    {
        // 1. Rechercher le ticket par token
        $ticket = $entityManager->getRepository(Ticket::class)->findOneBy(['uniqueToken' => $token]);

        if (!$ticket) {
            return $this->render('check_in/error.html.twig', [
                'message' => 'Ticket invalide ou introuvable.'
            ]);
        }

        // 2. Vérifier si l'événement est terminé ou annulé (optionnel, selon règles métier)
        $event = $ticket->getEvent();

        // 3. Vérifier si déjà checké
        if ($ticket->getCheckedInAt() !== null) {
            return $this->render('check_in/warning.html.twig', [
                'ticket' => $ticket,
                'message' => 'Ce ticket a déjà été validé le ' . $ticket->getCheckedInAt()->format('d/m/Y à H:i')
            ]);
        }

        // 4. Valider le check-in
        $ticket->setCheckedInAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->render('check_in/success.html.twig', [
            'ticket' => $ticket
        ]);
    }
}


