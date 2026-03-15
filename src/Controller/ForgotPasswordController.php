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
    // ==============================
    // 1️⃣ PAGE MOT DE PASSE OUBLIÉ
    // ==============================
    #[Route('/forgot-password', name: 'forgot_password_page', methods: ['GET'])]
    public function showForgotPassword(): Response
    {
        return $this->render('forgot_password/forgot_password.html.twig');
    }

    // ==========================================
    // 2️⃣ ENVOI DU CODE DE RÉINITIALISATION
    // ==========================================
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $email = $request->request->get('email');

        if (!$email) {
            return new JsonResponse(['message' => 'Email requis'], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);

        // Message générique (sécurité)
        if (!$user) {
            return new JsonResponse([
                'message' => 'Si cet email existe, un code a été envoyé'
            ]);
        }

        // Générer un code à 6 chiffres
        $code = random_int(100000, 999999);

        // Supprimer les anciens tokens
        $em->createQuery(
            'DELETE FROM App\Entity\PasswordResetToken t WHERE t.user = :user'
        )->setParameter('user', $user)->execute();

        // Créer un nouveau token
        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setToken((string) $code);
        $token->setExpiresAt(new \DateTime('+15 minutes'));

        $em->persist($token);
        $em->flush();

        // Envoi de l'email
        try {
            $emailObj = (new Email())
                ->from('kacemi396@gmail.com')
                ->to($email)
                ->subject('Code de réinitialisation du mot de passe')
                ->html("
                    <h2>Réinitialisation de mot de passe</h2>
                    <p>Votre code de vérification :</p>
                    <p style='font-size:28px;font-weight:bold;'>$code</p>
                    <p>Valable pendant <strong>15 minutes</strong>.</p>
                ");

            $mailer->send($emailObj);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur lors de l’envoi de l’email'], 500);
        }

        return new JsonResponse(['message' => 'Code envoyé avec succès']);
    }

    // =============================
    // 3️⃣ VÉRIFICATION DU CODE
    // =============================
    #[Route('/verify-code', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(
        Request $request,
        PasswordResetTokenRepository $tokenRepo,
        UsersRepository $usersRepo
    ): JsonResponse {
        $email = $request->request->get('email');
        $code = $request->request->get('code');

        if (!$email || !$code) {
            return new JsonResponse(['message' => 'Email et code requis'], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['message' => 'Code invalide'], 400);
        }

        $token = $tokenRepo->findOneBy(
            ['user' => $user, 'token' => $code],
            ['id' => 'DESC']
        );

        if (!$token || $token->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Code invalide ou expiré'], 400);
        }

        return new JsonResponse(['message' => 'Code vérifié avec succès']);
    }

    // ======================================
    // 4️⃣ RÉINITIALISATION DU MOT DE PASSE
    // ======================================
    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UsersRepository $usersRepo,
        EntityManagerInterface $em,
        PasswordResetTokenRepository $tokenRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $code = $request->request->get('code');

        if (!$email || !$password || !$code) {
            return new JsonResponse(['message' => 'Tous les champs sont requis'], 400);
        }

        if (strlen($password) < 6) {
            return new JsonResponse([
                'message' => 'Mot de passe trop court (min 6 caractères)'
            ], 400);
        }

        $user = $usersRepo->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['message' => 'Code invalide'], 400);
        }

        $token = $tokenRepo->findOneBy(
            ['user' => $user, 'token' => $code],
            ['id' => 'DESC']
        );

        if (!$token || $token->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['message' => 'Code invalide ou expiré'], 400);
        }

        // 🔐 HACHAGE SÉCURISÉ
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Supprimer le token
        $em->remove($token);
        $em->flush();

        return new JsonResponse([
            'message' => 'Mot de passe réinitialisé avec succès'
        ]);
    }
}
