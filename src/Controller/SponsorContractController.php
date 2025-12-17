<?php

namespace App\Controller;

use App\Entity\SponsorContract;
use App\Form\SponsorContractType;
use App\Repository\SponsorContractRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsor/contract')]
final class SponsorContractController extends AbstractController
{
    #[Route('/', name: 'app_sponsor_contract_index', methods: ['GET'])]
    public function index(SponsorContractRepository $sponsorContractRepository): Response
    {
        return $this->render('sponsor_contract/index.html.twig', [
            'sponsor_contracts' => $sponsorContractRepository->findAllWithSponsor(),
        ]);
    }

    #[Route('/new', name: 'app_sponsor_contract_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, PdfGenerator $pdfGenerator): Response
    {
        $sponsorContract = new SponsorContract();
        $form = $this->createForm(SponsorContractType::class, $sponsorContract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsorContract);
            $entityManager->flush();

            // Générer le PDF du contrat
            $companyInfo = [
                'name' => $_ENV['COMPANY_NAME'] ?? 'ARTEDU',
                'phone' => $_ENV['COMPANY_PHONE'] ?? null,
                'email' => $_ENV['COMPANY_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? null,
                'siret' => $_ENV['COMPANY_SIRET'] ?? '98.03.799.200.1.2',
                'address' => $_ENV['COMPANY_ADDRESS'] ?? '10 Rue des Entrepreneurs',
                'city' => $_ENV['COMPANY_CITY'] ?? '10000 Tunis',
                'rcs' => $_ENV['COMPANY_RCS'] ?? 'RCS Packs B 123 401 16',
            ];

            $pdfContent = $pdfGenerator->generateContractPdf($sponsorContract, $companyInfo);

            // Créer la réponse avec le PDF
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', sprintf(
                'attachment; filename="contrat-sponsoring-%s.pdf"',
                $sponsorContract->getContractNumber()
            ));

            $this->addFlash('success', 'Le contrat a été créé avec succès. Le PDF a été généré.');
            return $response;
        }

        return $this->render('sponsor_contract/new.html.twig', [
            'sponsor_contract' => $sponsorContract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_contract_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(SponsorContract $sponsorContract): Response
    {
        return $this->render('sponsor_contract/show.html.twig', [
            'sponsor_contract' => $sponsorContract,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_sponsor_contract_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function downloadPdf(SponsorContract $sponsorContract, PdfGenerator $pdfGenerator): Response
    {
        $companyInfo = [
            'name' => $_ENV['COMPANY_NAME'] ?? 'ARTEDU',
            'phone' => $_ENV['COMPANY_PHONE'] ?? null,
            'email' => $_ENV['COMPANY_EMAIL'] ?? $_ENV['ADMIN_EMAIL'] ?? null,
            'siret' => $_ENV['COMPANY_SIRET'] ?? '98.03.799.200.1.2',
            'address' => $_ENV['COMPANY_ADDRESS'] ?? '10 Rue des Entrepreneurs',
            'city' => $_ENV['COMPANY_CITY'] ?? '10000 Tunis',
            'rcs' => $_ENV['COMPANY_RCS'] ?? 'RCS Packs B 123 401 16',
        ];

        $pdfContent = $pdfGenerator->generateContractPdf($sponsorContract, $companyInfo);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf(
            'attachment; filename="contrat-sponsoring-%s.pdf"',
            $sponsorContract->getContractNumber()
        ));

        return $response;
    }

    #[Route('/{id}/edit', name: 'app_sponsor_contract_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, SponsorContract $sponsorContract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SponsorContractType::class, $sponsorContract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été modifié avec succès.');
            return $this->redirectToRoute('app_sponsor_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor_contract/edit.html.twig', [
            'sponsor_contract' => $sponsorContract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_sponsor_contract_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, SponsorContract $sponsorContract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsorContract->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($sponsorContract);
            $entityManager->flush();
            $this->addFlash('success', 'Le contrat a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_sponsor_contract_index', [], Response::HTTP_SEE_OTHER);
    }
}


