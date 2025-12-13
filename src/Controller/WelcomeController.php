<?php

namespace App\Controller;

use App\Entity\Users;
use App\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

final class WelcomeController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome')]
    public function index(): Response
    {
        return $this->render('welcome/index.html.twig', [
            'controller_name' => 'WelcomeController',
            'hide_navbar' => true,
        ]);
    }

    #[Route('/welcome/new', name: 'app_welcome_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Si c'est une requête GET, afficher le formulaire
        if ($request->isMethod('GET')) {
            return $this->render('welcome/new.html.twig');
        }

        // Si c'est une requête POST, traiter le formulaire
        try {
            // 1. Vérifier le token CSRF
            $token = $request->request->get('_csrf_token');
            if (!$csrfTokenManager->isTokenValid(new CsrfToken('signup', $token))) {
                $this->addFlash('danger', '❌ Token CSRF invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_welcome');
            }

            // 2. Récupérer les données
            $fullname = trim($request->request->get('fullname', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $roleValue = $request->request->get('role', '');
            $acceptTerms = $request->request->get('acceptTerms');

            // 3. Validations
            $errors = [];

            if (empty($fullname) || strlen($fullname) < 2) {
                $errors[] = "Le nom complet doit contenir au moins 2 caractères.";
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'adresse email n'est pas valide.";
            }

            if (empty($password) || strlen($password) < 8) {
                $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
                $errors[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.";
            }

            if (empty($roleValue) || !in_array($roleValue, ['USER', 'AGENT'])) {
                $errors[] = "Veuillez choisir un profil valide (Vendeur ou Client).";
            }

            if (!$acceptTerms) {
                $errors[] = "Vous devez accepter les conditions d'utilisation.";
            }

            // Vérifier si l'email existe déjà
            $existingUser = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }

            // Si des erreurs, rediriger
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('danger', $error);
                }
                return $this->redirectToRoute('app_welcome');
            }

            // 4. Créer le nouvel utilisateur
            $user = new Users();
            $user->setFullname($fullname);
            $user->setEmail($email);
            
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            
            // Définir le rôle selon le choix
            if ($roleValue === 'USER') {
                $user->setRole(Role::USER);
            } elseif ($roleValue === 'AGENT') {
                $user->setRole(Role::AGENT);
            }
            
            // isActive est déjà true par défaut dans le constructeur
            $user->setIsActive(true);

            // 5. Sauvegarder en base de données
            $em->persist($user);
            $em->flush();

            // 6. Message de succès et redirection
            $this->addFlash('success', '🎉 Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_login');

        } catch (\Exception $e) {
            $this->addFlash('danger', '❌ Erreur lors de la création du compte : ' . $e->getMessage());
            return $this->redirectToRoute('app_welcome');
        }
    }
}