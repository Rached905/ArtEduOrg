<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Entity\Event;
use App\Form\SponsorType;
use App\Repository\SponsorContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\SponsorshipRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

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

    #[Route('/notify-expiring-contracts', name: 'app_sponsor_notify_expiring_contracts', methods: ['GET'])]
    public function notifyExpiringContracts(
        SponsorContractRepository $sponsorContractRepository,
        MailerInterface $mailer,
        Environment $twig
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $days = 30;
        $now = new \DateTimeImmutable();
        $expirationDate = $now->modify("+{$days} days");

        $expiringContracts = $sponsorContractRepository->findExpiringWithinDays($days);

        if (empty($expiringContracts)) {
            $this->addFlash('info', sprintf('Aucun contrat n\'expire dans les %d prochains jours.', $days));
            return $this->redirectToRoute('app_sponsor_stats');
        }

        $contractsData = [];
        foreach ($expiringContracts as $contract) {
            $expiresAt = $contract->getExpiresAt();
            $daysUntilExpiration = $now->diff($expiresAt)->days;

            $contractsData[] = [
                'contractNumber' => $contract->getContractNumber(),
                'sponsor' => $contract->getSponsor(),
                'expiresAt' => $expiresAt,
                'daysUntilExpiration' => $daysUntilExpiration,
                'level' => $contract->getLevel(),
            ];
        }

        try {
            $htmlContent = $twig->render('emails/expiring_contracts_notification.html.twig', [
                'contracts' => $contractsData,
                'days' => $days,
                'periodStart' => $now,
                'periodEnd' => $expirationDate,
            ]);

            $textContent = "Notification de Contrats Expirants\n\n";
            $textContent .= sprintf(
                "Nous vous informons que %d contrat(s) expirent dans les %d prochains jours.\n\n",
                count($contractsData),
                $days
            );
            $textContent .= "Détails des contrats :\n";
            foreach ($contractsData as $contract) {
                $textContent .= sprintf(
                    "- %s (%s) - Expire le %s (%d jours restants)\n",
                    $contract['contractNumber'],
                    $contract['sponsor'] ? $contract['sponsor']->getName() : 'N/A',
                    $contract['expiresAt']->format('d/m/Y'),
                    $contract['daysUntilExpiration']
                );
            }

            $email = (new Email())
                ->from('noreply@votresite.com')
                ->to('kacemi396@gmail.com')
                ->subject(sprintf('⚠️ %d Contrat(s) Expirant(s) dans les %d Prochains Jours', count($contractsData), $days))
                ->text($textContent)
                ->html($htmlContent);

            $mailer->send($email);

            $this->addFlash(
                'success',
                sprintf(
                    'Email de notification envoyé à %s pour %d contrat(s) expirant(s) dans les %d prochains jours.',
                    'kacemi396@gmail.com',
                    count($contractsData),
                    $days
                )
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_sponsor_stats');
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


