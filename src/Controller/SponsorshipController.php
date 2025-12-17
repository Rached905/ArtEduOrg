<?php

namespace App\Controller;

use App\Entity\Sponsorship;
use App\Form\SponsorshipType;
use App\Repository\SponsorshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsorship')]
final class SponsorshipController extends AbstractController
{
    #[Route('/', name: 'app_sponsorship_index', methods: ['GET'])]
    public function index(SponsorshipRepository $sponsorshipRepository): Response
    {
        return $this->render('sponsorship/index.html.twig', [
            'sponsorships' => $sponsorshipRepository->findAllWithSponsor(),
        ]);
    }

    #[Route('/new', name: 'app_sponsorship_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sponsorship = new Sponsorship();
        $form = $this->createForm(SponsorshipType::class, $sponsorship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsorship);
            $entityManager->flush();

            $this->addFlash('success', 'Le parrainage a été créé avec succès.');
            return $this->redirectToRoute('app_sponsorship_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsorship/new.html.twig', [
            'sponsorship' => $sponsorship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsorship_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Sponsorship $sponsorship): Response
    {
        return $this->render('sponsorship/show.html.twig', [
            'sponsorship' => $sponsorship,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsorship_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Sponsorship $sponsorship, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SponsorshipType::class, $sponsorship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le parrainage a été modifié avec succès.');
            return $this->redirectToRoute('app_sponsorship_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsorship/edit.html.twig', [
            'sponsorship' => $sponsorship,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_sponsorship_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Sponsorship $sponsorship, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsorship->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($sponsorship);
            $entityManager->flush();
            $this->addFlash('success', 'Le parrainage a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_sponsorship_index', [], Response::HTTP_SEE_OTHER);
    }
}


