<?php

namespace App\Event\Sale;

use App\Entity\Sale;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché lorsqu'un paiement est confirmé
 * Permet de découpler les services et d'éviter les conflits
 */
class PaymentConfirmedEvent extends Event
{
    public const NAME = 'sale.payment.confirmed';

    public function __construct(
        private readonly Sale $sale,
        private readonly string $clientEmail,
        private readonly string $clientName,
        private readonly float $amount,
        private readonly ?string $invoicePath = null,
        private readonly ?string $paymentIntentId = null
    ) {
    }

    public function getSale(): Sale
    {
        return $this->sale;
    }

    public function getClientEmail(): string
    {
        return $this->clientEmail;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getInvoicePath(): ?string
    {
        return $this->invoicePath;
    }

    public function getPaymentIntentId(): ?string
    {
        return $this->paymentIntentId;
    }
}

