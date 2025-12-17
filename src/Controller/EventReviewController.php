<?php

namespace App\Controller;

use App\Entity\EventReview;
use App\Entity\Event;
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
}


