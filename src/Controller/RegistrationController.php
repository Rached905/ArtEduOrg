<?php

namespace App\Controller;

use App\Entity\Users;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/choose-role', name: 'app_choose_role')]
    public function chooseRole(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur a déjà un rôle, rediriger selon le rôle
        if ($user->getRole()) {
            return $this->redirectUserByRole($user);
        }

        return $this->render('registration/choose_role.html.twig');
    }

    #[Route('/set-role/{role}', name: 'app_set_role')]
    public function setRole(string $role, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Définir le rôle selon le choix
        if ($role === 'client') {
            $user->setRole(Role::AGENT); // AGENT = CLIENT
        } elseif ($role === 'vendeur') {
            $user->setRole(Role::USER);  // USER = VENDEUR
        } else {
            $this->addFlash('error', 'Rôle invalide');
            return $this->redirectToRoute('app_choose_role');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Votre rôle a été défini avec succès !');
        
        // ✅ Rediriger selon le rôle choisi
        return $this->redirectUserByRole($user);
    }

    /**
     * Redirige l'utilisateur selon son rôle
     */
    private function redirectUserByRole($user): Response
    {
        $role = $user->getRole();

        if ($role === Role::ADMIN) {
            return $this->redirectToRoute('app_dashboard');
        }
        
        if ($role === Role::USER) {
            return $this->redirectToRoute('app_vendeur');
        }
        
        if ($role === Role::AGENT) {
            return $this->redirectToRoute('app_client');
        }

        // Fallback
        return $this->redirectToRoute('app_welcome');
    }
}