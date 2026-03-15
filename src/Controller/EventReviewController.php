<?php

namespace App\Controller;

use App\Entity\EventReview;
use App\Entity\Event;
use App\Entity\Users;
use App\Form\EventReviewType;
use App\Repository\EventReviewRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event/review')]
final class EventReviewController extends AbstractController
{
    #[Route('/', name: 'app_event_review_index', methods: ['GET'])]
    public function index(EventReviewRepository $eventReviewRepository): Response
    {
        return $this->render('event_review/index.html.twig', [
            'event_reviews' => $eventReviewRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_event_review_new', methods: ['GET', 'POST'])]
    #[Route('/new/{eventId}', name: 'app_event_review_new_for_event', methods: ['GET', 'POST'], requirements: ['eventId' => '\d+'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EventRepository $eventRepository, ?int $eventId = null): Response
    {
        $eventReview = new EventReview();
        
        // Si un eventId est fourni, pré-remplir l'événement
        if ($eventId) {
            $event = $eventRepository->find($eventId);
            if ($event) {
                $eventReview->setEvent($event);
            }
        }
        
        $form = $this->createForm(EventReviewType::class, $eventReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Associer l'utilisateur connecté si disponible
            $user = $this->getUser();
            if ($user instanceof Users) {
                $eventReview->setUser($user);
            }
            
            $entityManager->persist($eventReview);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été créé avec succès.');
            
            // Rediriger vers l'événement si on vient d'un événement spécifique
            if ($eventId) {
                return $this->redirectToRoute('app_event_show', ['id' => $eventId], Response::HTTP_SEE_OTHER);
            }
            
            return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_review/new.html.twig', [
            'event_review' => $eventReview,
            'form' => $form,
            'eventId' => $eventId,
        ]);
    }

    #[Route('/{id}', name: 'app_event_review_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(EventReview $eventReview): Response
    {
        return $this->render('event_review/show.html.twig', [
            'event_review' => $eventReview,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_review_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, EventReview $eventReview, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventReviewType::class, $eventReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été modifié avec succès.');
            return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_review/edit.html.twig', [
            'event_review' => $eventReview,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_review_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, EventReview $eventReview, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$eventReview->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($eventReview);
            $entityManager->flush();
            $this->addFlash('success', 'L\'avis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/vendor/new/{eventId}', name: 'app_event_review_vendor_new', methods: ['POST'], requirements: ['eventId' => '\d+'])]
    public function vendorNew(Request $request, int $eventId, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_VENDOR');
        
        $event = $eventRepository->find($eventId);
        if (!$event) {
            return $this->json(['success' => false, 'message' => 'Événement non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $eventReview = new EventReview();
        $eventReview->setEvent($event);
        
        // Associer l'utilisateur connecté
        $user = $this->getUser();
        if ($user instanceof \App\Entity\Users) {
            $eventReview->setUser($user);
        }

        $rating = $request->request->getInt('rating');
        $comment = $request->request->getString('comment');

        if ($rating < 1 || $rating > 5) {
            return $this->json(['success' => false, 'message' => 'La note doit être entre 1 et 5.'], Response::HTTP_BAD_REQUEST);
        }

        $eventReview->setRating($rating);
        $eventReview->setComment($comment ?: null);

        $entityManager->persist($eventReview);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Votre avis a été enregistré avec succès.',
            'review' => [
                'id' => $eventReview->getId(),
                'rating' => $eventReview->getRating(),
                'comment' => $eventReview->getComment(),
                'createdAt' => $eventReview->getCreatedAt()->format('d/m/Y H:i'),
                'userName' => $user ? $user->getFullname() : 'Anonyme',
            ]
        ]);
    }
}


