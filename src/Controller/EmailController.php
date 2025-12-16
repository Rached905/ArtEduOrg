<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    #[Route('/send-email', name: 'app_send_email', methods: ['POST'])]
    public function sendEmail(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $message = $data['message'] ?? '';
        $recipientEmail = $data['email'] ?? '';

        if (empty($message) || empty($recipientEmail)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message ou email manquant'
            ], 400);
        }

        try {
            $email = (new Email())
                ->from('noreply@votresite.com')
                ->to($recipientEmail)
                ->subject('Nouveau message')
                ->text($message)
                ->html('<p>' . nl2br(htmlspecialchars($message)) . '</p>');

            $mailer->send($email);

            return new JsonResponse([
                'success' => true,
                'message' => 'Email envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur lors de l\'envoi : ' . $e->getMessage()
            ], 500);
        }
    }
}