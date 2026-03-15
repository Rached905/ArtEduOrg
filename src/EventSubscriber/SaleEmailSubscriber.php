<?php

namespace App\EventSubscriber;

use App\Event\Sale\PaymentConfirmedEvent;
use App\Service\Sale\SaleMailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber pour gérer les emails liés au module Sale
 * Utilise le système d'événements Symfony pour découpler les services
 */
class SaleEmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SaleMailerService $mailerService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentConfirmedEvent::NAME => 'onPaymentConfirmed',
        ];
    }

    /**
     * Envoie un email de confirmation lorsqu'un paiement est confirmé
     */
    public function onPaymentConfirmed(PaymentConfirmedEvent $event): void
    {
        $sale = $event->getSale();
        $clientEmail = $event->getClientEmail();
        $clientName = $event->getClientName();
        $amount = $event->getAmount();
        $invoicePath = $event->getInvoicePath();
        $paymentIntentId = $event->getPaymentIntentId();

        // 1. Envoyer l'email de confirmation à l'acheteur
        if ($invoicePath) {
            // Achat direct avec facture
            $this->mailerService->sendDirectPurchaseConfirmation(
                $sale,
                $clientName,
                $clientEmail,
                $amount,
                $invoicePath
            );
        } else {
            // Paiement sans facture
            $this->mailerService->sendPaymentConfirmedNotification(
                $sale,
                $clientEmail,
                $clientName,
                $amount
            );
        }

        // 2. Envoyer l'email de notification au vendeur
        $this->mailerService->sendSaleSoldNotificationToVendor(
            $sale,
            $amount,
            $paymentIntentId,
            $clientName,
            $clientEmail
        );
    }
}

