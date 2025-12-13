<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPasswordController extends AbstractController
{
    // Afficher la page de réinitialisation
    #[Route('/forgot-password', name: 'forgot_password_page', methods: ['GET'])]
    public function showForgotPassword(): Response
    {
        return $this->render('forgot_password/forgot_password.html.twig');
    }

    // Envoyer le code de vérification par email
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request, 
        UsersRepository $usersRepo, 
        EntityManagerInterface $em, 
        MailerInterface $mailer
    ): JsonResponse
    {
        $email = $request->request->get('email');
        
        if (!$email) {
            return new JsonResponse(['message' => 'Email requis'], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['message' => 'Email non trouvé'], 404);
        }

        // Générer un code de vérification à 6 chiffres
        $code = random_int(100000, 999999);
        
        // Créer le token de réinitialisation
        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setToken((string)$code);
        $token->setExpiresAt(new \DateTime('+15 minutes'));
        
        $em->persist($token);
        $em->flush();

        // Envoyer l'email avec le code
        try {
            $emailObj = (new Email())
                ->from('kacemi396@gmail.com')
                ->to($email)
                ->subject('Code de vérification - Réinitialisation de mot de passe')
                ->html("
                    <h2>Réinitialisation de mot de passe</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                    <p>Votre code de vérification est : <strong style='font-size: 24px;'>$code</strong></p>
                    <p>Ce code est valable pendant 15 minutes.</p>
                    <p>Si vous n'avez pas fait cette demande, ignorez cet email.</p>
                ");

            $mailer->send($emailObj);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }

        return new JsonResponse(['message' => 'Code envoyé avec succès']);
    }

    // Vérifier le code de vérification
    #[Route('/verify-code', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(
        Request $request, 
        PasswordResetTokenRepository $tokenRepo,
        UsersRepository $usersRepo
    ): JsonResponse
    {
        $email = $request->request->get('email');
        $code = $request->request->get('code');

        if (!$email || !$code) {
            return new JsonResponse(['message' => 'Email et code requis'], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Chercher le token le plus récent pour cet utilisateur
        $token = $tokenRepo->findOneBy(
            ['token' => $code, 'user' => $user], 
            ['id' => 'DESC']
        );

        if (!$token) {
            return new JsonResponse(['message' => 'Code invalide'], 400);
        }

        // Vérifier si le token n'est pas expiré
        if ($token->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Code expiré. Veuillez en demander un nouveau.'], 400);
        }

        return new JsonResponse(['message' => 'Code vérifié avec succès']);
    }

    // Réinitialiser le mot de passe
    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request, 
        UsersRepository $usersRepo, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $hasher,
        PasswordResetTokenRepository $tokenRepo
    ): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $code = $request->request->get('code');

        if (!$email || !$password || !$code) {
            return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        }

        // Vérifier la longueur du mot de passe
        if (strlen($password) < 6) {
            return new JsonResponse(['message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier le token
        $token = $tokenRepo->findOneBy(
            ['token' => $code, 'user' => $user], 
            ['id' => 'DESC']
        );

        if (!$token) {
            return new JsonResponse(['message' => 'Code invalide'], 400);
        }

        // Vérifier si le token n'est pas expiré
        if ($token->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Code expiré'], 400);
        }

        // Hasher et mettre à jour le mot de passe
        $hashedPassword = $hasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Supprimer le token utilisé pour éviter sa réutilisation
        $em->remove($token);
        $em->flush();

        return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès']);
    }

    
}