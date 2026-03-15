<?php

namespace App\Service\Sale;

use App\Entity\Sale;
use App\Service\Interface\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Service d'envoi d'email spécifique au module Sale
 * Utilise un namespace dédié pour éviter les conflits avec d'autres modules
 */
class SaleMailerService implements EmailServiceInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly string $fromEmail,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * Envoie un email générique (implémentation de l'interface)
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = []
    ): void {
        try {
            $email = (new Email())
                ->from($this->fromEmail)
                ->to($to)
                ->subject($subject)
                ->html($this->twig->render($template, $context));

            // Attacher les fichiers si fournis
            foreach ($attachments as $attachment) {
                if (is_array($attachment) && isset($attachment['path'])) {
                    $path = $attachment['path'];
                    $name = $attachment['name'] ?? basename($path);
                    $contentType = $attachment['contentType'] ?? 'application/octet-stream';
                    
                    if (file_exists($path)) {
                        $email->attachFromPath($path, $name, $contentType);
                    }
                }
            }

            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi email: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie une notification de changement de statut
     */
    public function sendStatusUpdatedNotification(Sale $sale, string $recipientEmail, string $recipientName, string $oldStatus, string $newStatus): void
    {
        try {
            $this->sendEmail(
                $recipientEmail,
                'Mise à jour du statut - ' . $sale->getTitle(),
                'emails/status_updated.html.twig',
                [
                    'sale' => $sale,
                    'recipientName' => $recipientName,
                    'oldStatus' => $oldStatus,
                    'newStatus' => $newStatus,
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email statut mis à jour: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification de paiement confirmé
     */
    public function sendPaymentConfirmedNotification(Sale $sale, string $recipientEmail, string $recipientName, float $amount): void
    {
        try {
            $this->sendEmail(
                $recipientEmail,
                'Paiement confirmé - ' . $sale->getTitle(),
                'emails/payment_confirmed.html.twig',
                [
                    'sale' => $sale,
                    'recipientName' => $recipientName,
                    'amount' => $amount,
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email paiement confirmé: ' . $e->getMessage());
        }
    }

    /**
     * Envoie un email de test (pour l'API)
     */
    public function sendTestEmail(string $to, string $subject, string $message): void
    {
        try {
            $this->sendEmail(
                $to,
                $subject,
                'emails/test_email.html.twig',
                [
                    'message' => $message,
                    'recipientEmail' => $to,
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email test: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie une confirmation d'achat direct avec facture
     */
    public function sendDirectPurchaseConfirmation(
        Sale $sale,
        string $clientName,
        string $clientEmail,
        float $amount,
        string $invoicePath
    ): void {
        try {
            $invoiceFullPath = $this->parameterBag->get('kernel.project_dir') . '/public/' . ltrim($invoicePath, '/');
            
            $attachments = [];
            if (file_exists($invoiceFullPath)) {
                $attachments[] = [
                    'path' => $invoiceFullPath,
                    'name' => 'facture.pdf',
                    'contentType' => 'application/pdf',
                ];
            }

            $this->sendEmail(
                $clientEmail,
                'Confirmation d\'achat - ' . $sale->getTitle(),
                'emails/direct_purchase_confirmation.html.twig',
                [
                    'sale' => $sale,
                    'clientName' => $clientName,
                    'amount' => $amount,
                    'invoicePath' => $invoicePath,
                ],
                $attachments
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email confirmation achat direct: ' . $e->getMessage());
        }
    }

    /**
     * Envoie une notification de vente au vendeur
     */
    public function sendSaleSoldNotificationToVendor(
        Sale $sale,
        float $amount,
        ?string $paymentIntentId = null,
        ?string $buyerName = null,
        ?string $buyerEmail = null
    ): void {
        try {
            // Récupérer l'email du vendeur
            $vendorEmail = null;
            if ($sale->getVendeur() && $sale->getVendeur()->getEmail()) {
                $vendorEmail = $sale->getVendeur()->getEmail();
            }

            // Si pas de vendeur ou pas d'email, on ne peut pas envoyer
            if (!$vendorEmail) {
                error_log('Impossible d\'envoyer l\'email au vendeur : email non trouvé pour la vente #' . $sale->getId());
                return;
            }

            // Utiliser les informations de l'acheteur depuis la relation ou les paramètres
            $finalBuyerName = $buyerName;
            $finalBuyerEmail = $buyerEmail;
            
            if ($sale->getAcheteur()) {
                $finalBuyerName = $sale->getAcheteur()->getFullname() ?? $finalBuyerName;
                $finalBuyerEmail = $sale->getAcheteur()->getEmail() ?? $finalBuyerEmail;
            }

            $this->sendEmail(
                $vendorEmail,
                'Votre œuvre a été vendue - ' . $sale->getTitle(),
                'emails/sale_sold_notification.html.twig',
                [
                    'sale' => $sale,
                    'amount' => $amount,
                    'paymentIntentId' => $paymentIntentId,
                    'buyerName' => $finalBuyerName,
                    'buyerEmail' => $finalBuyerEmail,
                ]
            );
        } catch (\Exception $e) {
            error_log('Erreur envoi email notification vente au vendeur: ' . $e->getMessage());
        }
    }
}

