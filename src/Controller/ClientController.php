<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(): Response
    {
        // Récupérer l'utilisateur connecté (optionnel)
        $user = $this->getUser();

        return $this->render('client/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/evenements', name: 'app_client_evenements')]
    public function evenements(): Response
    {
        return $this->render('client/evenements.html.twig');
    }

    #[Route('/mes-achats', name: 'app_client_mes_achats')]
    public function mesAchats(): Response
    {
        // Vérifier que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('client/mes_achats.html.twig');
    }

    #[Route('/mes-favoris', name: 'app_client_favoris')]
    public function mesFavoris(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('client/favoris.html.twig');
    }
}