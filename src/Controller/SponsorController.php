<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Entity\Event;
use App\Form\SponsorType;
use App\Repository\SponsorRepository;
use App\Repository\SponsorContractRepository;
use App\Repository\SponsorshipRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsor')]
final class SponsorController extends AbstractController
{
    #[Route('/stats', name: 'app_sponsor_stats', methods: ['GET'])]
    public function stats(
        SponsorRepository $sponsorRepository,
        SponsorContractRepository $sponsorContractRepository,
        SponsorshipRepository $sponsorshipRepository
    ): Response
    {
        $now = new \DateTimeImmutable();
        
        // Statistiques générales
        $totalSponsors = $sponsorRepository->count([]);
        $totalContracts = $sponsorContractRepository->count([]);
        $totalSponsorships = $sponsorshipRepository->count([]);
        $totalSponsoring = $totalSponsors + $totalContracts + $totalSponsorships;

        // Contrats actifs (non expirés)
        $allContracts = $sponsorContractRepository->findAll();
        $activeContracts = 0;
        $expiredContracts = 0;
        $totalContractValue = 0; // Note: SponsorContract n'a pas de propriété amount
        
        foreach ($allContracts as $contract) {
            $expiresAt = $contract->getExpiresAt();
            if ($expiresAt) {
                $expiresAtImmutable = $expiresAt instanceof \DateTimeImmutable 
                    ? $expiresAt 
                    : \DateTimeImmutable::createFromMutable($expiresAt);
                if ($expiresAtImmutable >= $now) {
                    $activeContracts++;
                } else {
                    $expiredContracts++;
                }
            } else {
                $expiredContracts++;
            }
        }

        // Parrainages actifs
        // Note: Sponsorship n'a pas de date d'expiration, donc tous sont considérés comme actifs
        $allSponsorships = $sponsorshipRepository->findAll();
        $activeSponsorships = count($allSponsorships);
        $totalSponsorshipValue = 0;
        
        foreach ($allSponsorships as $sponsorship) {
            $totalSponsorshipValue += $sponsorship->getAmount() ?? 0;
        }

        // Statistiques mensuelles (approximation basée sur les IDs récents)
        // Note: Ces statistiques seront plus précises si createdAt est ajouté aux entités
        $sponsorsThisMonth = 0; // À mettre à jour si createdAt est ajouté à Sponsor
        $contractsThisMonth = 0; // À mettre à jour si createdAt est ajouté à SponsorContract
        $sponsorshipsThisMonth = 0; // À mettre à jour si createdAt est ajouté à Sponsorship

        // Derniers sponsors
        $recentSponsors = $sponsorRepository->findBy([], ['id' => 'DESC'], 5);
        
        // Derniers contrats
        $recentContracts = $sponsorContractRepository->findRecentWithSponsor(5);
        
        // Derniers parrainages
        $recentSponsorships = $sponsorshipRepository->findRecentWithSponsor(5);

        return $this->render('sponsor/stats.html.twig', [
            'total_sponsors' => $totalSponsors,
            'total_contracts' => $totalContracts,
            'total_sponsorships' => $totalSponsorships,
            'total_sponsoring' => $totalSponsoring,
            'active_contracts' => $activeContracts,
            'expired_contracts' => $expiredContracts,
            'active_sponsorships' => $activeSponsorships,
            'total_contract_value' => $totalContractValue,
            'total_sponsorship_value' => $totalSponsorshipValue,
            'sponsors_this_month' => $sponsorsThisMonth,
            'contracts_this_month' => $contractsThisMonth,
            'sponsorships_this_month' => $sponsorshipsThisMonth,
            'recent_sponsors' => $recentSponsors,
            'recent_contracts' => $recentContracts,
            'recent_sponsorships' => $recentSponsorships,
            'now' => $now,
        ]);
    }

    #[Route('/', name: 'app_sponsor_index')]
    public function index(
        Request $request, 
        SponsorRepository $sponsorRepository,
        SponsorContractRepository $sponsorContractRepository,
        SponsorshipRepository $sponsorshipRepository
    ): Response
    {
        // Recherche depuis la navbar (paramètre GET 'q')
        $searchQuery = $request->query->get('q', '');

        $sponsors = $searchQuery
            ? $sponsorRepository->searchByCriteria(['search' => $searchQuery])
            : $sponsorRepository->findAll();

        // Statistiques
        $totalSponsors = $sponsorRepository->count([]);
        $totalContracts = $sponsorContractRepository->count([]);
        $totalSponsorships = $sponsorshipRepository->count([]);
        $totalSponsoring = $totalSponsors + $totalContracts + $totalSponsorships;

        // Derniers éléments
        $recentSponsors = $sponsorRepository->findBy([], ['id' => 'DESC'], 2);
        $recentContracts = $sponsorContractRepository->findRecentWithSponsor(1);
        $recentSponsorships = $sponsorshipRepository->findRecentWithSponsor(1);

        return $this->render('sponsor/index.html.twig', [
            'sponsors' => $sponsors,
            'stats' => [
                'total_sponsors' => $totalSponsors,
                'total_contracts' => $totalContracts,
                'total_sponsorships' => $totalSponsorships,
                'total_sponsoring' => $totalSponsoring,
            ],
            'recent_sponsors' => $recentSponsors,
            'recent_contracts' => $recentContracts,
            'recent_sponsorships' => $recentSponsorships,
        ]);
    }

    #[Route('/new', name: 'app_sponsor_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
                $sponsor->setLogo($logoFilename);
            }

            // Gestion du type de sponsorisation
            $sponsorType = $form->get('sponsorType')->getData();
            if ($sponsorType === 'event') {
                $event = $form->get('event')->getData();
                if ($event instanceof Event) {
                    $sponsor->addEvent($event);
                }
            }

            $entityManager->persist($sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Le sponsor a été créé avec succès.');
            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_show')]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsor_edit')]
    public function edit(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $oldLogo = $sponsor->getLogo();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                // Supprimer l'ancien logo si existe
                if ($oldLogo) {
                    $fileUploader->remove($oldLogo);
                }
                $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
                $sponsor->setLogo($logoFilename);
            }

            // Gestion du type de sponsorisation
            $sponsorType = $form->get('sponsorType')->getData();
            
            // Retirer tous les événements existants
            foreach ($sponsor->getEvents() as $event) {
                $sponsor->removeEvent($event);
            }
            
            if ($sponsorType === 'event') {
                $event = $form->get('event')->getData();
                if ($event instanceof Event) {
                    $sponsor->addEvent($event);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le sponsor a été modifié avec succès.');
            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_sponsor_delete')]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->request->getString('_token'))) {
            // Supprimer le logo si existe
            if ($sponsor->getLogo()) {
                $fileUploader->remove($sponsor->getLogo());
            }
            
            $entityManager->remove($sponsor);
            $entityManager->flush();
            $this->addFlash('success', 'Le sponsor a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }
}


