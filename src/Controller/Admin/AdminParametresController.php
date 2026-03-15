<?php

namespace App\Controller\Admin;

use App\Entity\AppSettings;
use App\Form\AppSettingsType;
use App\Repository\AppSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminParametresController extends AbstractController
{
    #[Route('/parametres', name: 'app_admin_parametres', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AppSettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $settings = $settingsRepository->getSettings();
        if ($settings->getId() === null) {
            $entityManager->persist($settings);
            $entityManager->flush();
        }

        $form = $this->createForm(AppSettingsType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Paramètres enregistrés avec succès.');
            return $this->redirectToRoute('app_admin_parametres');
        }

        return $this->render('admin/parametres/index.html.twig', [
            'settings' => $settings,
            'form' => $form,
        ]);
    }
}
