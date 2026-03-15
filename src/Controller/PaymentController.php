<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Sale;
use App\Event\Sale\PaymentConfirmedEvent;
use App\Repository\SaleRepository;
use App\Service\PaymentService;
use App\Service\InvoiceService;
use App\Service\Sale\SaleMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
    ) {
    }
    
    #[Route('/sale/{id}', name: 'app_payment_page', methods: ['GET'])]
    public function paymentPage(
        Sale $sale,
        Request $request
    ): Response {
        // Vérifier que la vente est disponible
        if ($sale->getStatus()->value !== 'disponible' && $sale->getStatus()->value !== 'en attente') {
            $this->addFlash('error', 'Cette vente n\'est plus disponible.');
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        
        if (!$sale->getAmount() || $sale->getAmount() <= 0) {
            $this->addFlash('error', 'Le prix de cette vente n\'est pas défini.');
            return $this->redirectToRoute('app_sale_details', ['id' => $sale->getId()]);
        }
        
        $amount = $sale->getAmount();

        // Récupérer la clé publique Stripe depuis les paramètres
        $stripePublicKey = $this->parameterBag->get('stripe_public_key');

        return $this->render('client/payment/payment.html.twig', [
            'sale' => $sale,
            'amount' => $amount,
            'isDirectPurchase' => true,
            'stripe_public_key' => $stripePublicKey,
        ]);
    }

    #[Route('/sale/{id}/create-intent', name: 'app_payment_create_intent', methods: ['POST'])]
    public function createPaymentIntent(
        Sale $sale,
        Request $request,
        PaymentService $paymentService
    ): JsonResponse {
        $clientName = $request->request->get('client_name', '');
        $clientEmail = $request->request->get('client_email', '');

        try {
            // Achat direct uniquement
            $amount = $sale->getAmount() ?? 0;
            if ($amount <= 0) {
                return new JsonResponse(['error' => 'Le prix de la vente n\'est pas défini'], Response::HTTP_BAD_REQUEST);
            }

            $result = $paymentService->createDirectPurchaseIntent($sale, $amount, $clientName, $clientEmail);
            
            return new JsonResponse([
                'clientSecret' => $result['clientSecret'],
                'paymentIntentId' => $result['paymentIntentId'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/confirm', name: 'app_payment_confirm', methods: ['POST'])]
    public function confirmPayment(
        Request $request,
        PaymentService $paymentService,
        SaleRepository $saleRepository,
        EntityManagerInterface $entityManager,
        InvoiceService $invoiceService,
        EventDispatcherInterface $eventDispatcher,
        SaleMailerService $mailerService
    ): JsonResponse {
        try {
            $paymentIntentId = $request->request->get('payment_intent_id');
            if (!$paymentIntentId && $request->getContent()) {
                parse_str($request->getContent(), $parsed);
                $paymentIntentId = $parsed['payment_intent_id'] ?? null;
            }
            if (!$paymentIntentId) {
                return new JsonResponse(['error' => 'Payment Intent ID manquant'], Response::HTTP_BAD_REQUEST);
            }

            $paymentIntent = $paymentService->getPaymentIntent($paymentIntentId);
            if (!$paymentIntent || $paymentIntent->status !== 'succeeded') {
                return new JsonResponse(['error' => 'Paiement non confirmé côté Stripe'], Response::HTTP_BAD_REQUEST);
            }

            $saleId = isset($paymentIntent->metadata->sale_id) ? (int) $paymentIntent->metadata->sale_id : 0;
            if ($saleId < 1) {
                return new JsonResponse(['error' => 'Vente introuvable (metadata manquante)'], Response::HTTP_BAD_REQUEST);
            }

            $sale = $saleRepository->find($saleId);
            if (!$sale) {
                return new JsonResponse(['error' => 'Vente introuvable'], Response::HTTP_NOT_FOUND);
            }

            $amount = $paymentIntent->amount / 100;
            $clientName = $request->request->get('client_name') ?: ($paymentIntent->metadata->client_name ?? 'Client');
            $clientEmail = $request->request->get('client_email') ?: ($paymentIntent->metadata->client_email ?? '');
            if ((!$clientName || !$clientEmail) && $request->getContent()) {
                parse_str($request->getContent(), $parsed);
                $clientName = $clientName ?: ($parsed['client_name'] ?? 'Client');
                $clientEmail = $clientEmail ?: ($parsed['client_email'] ?? '');
            }
            if (empty($clientEmail) || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                error_log('Email invalide pour l\'achat direct: ' . $clientEmail);
            }

            $invoicePath = null;
            try {
                $invoicePath = $invoiceService->generateDirectPurchaseInvoice(
                    $sale,
                    $clientName,
                    $clientEmail,
                    $amount,
                    $paymentIntentId
                );
            } catch (\Exception $e) {
                error_log('Erreur génération facture: ' . $e->getMessage());
            }

            $sale->setStatus(\App\Enum\SaleStatus::PAYE);
            $sale->setIsActive(false);
            if (!empty($clientEmail) && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                $sale->setBuyerEmail($clientEmail);
            }
            $user = $this->getUser();
            if ($user instanceof \App\Entity\Users) {
                $sale->setAcheteur($user);
            }

            $entityManager->flush();

            // Émettre un événement pour l'envoi d'email (découplé via EventSubscriber)
            if (!empty($clientEmail) && filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                $event = new PaymentConfirmedEvent(
                    $sale,
                    $clientEmail,
                    $clientName,
                    $amount,
                    $invoicePath,
                    $paymentIntentId
                );
                $eventDispatcher->dispatch($event, PaymentConfirmedEvent::NAME);
            } else {
                error_log('Email non envoyé à l\'acheteur - email invalide: ' . $clientEmail);
                if ($sale->getVendeur() && $sale->getVendeur()->getEmail()) {
                    try {
                        $mailerService->sendSaleSoldNotificationToVendor(
                            $sale,
                            $amount,
                            $paymentIntentId,
                            $clientName,
                            $clientEmail
                        );
                    } catch (\Exception $e) {
                        error_log('Erreur envoi email au vendeur: ' . $e->getMessage());
                    }
                }
            }

            // Notifications in-app (acheteur + vendeur)
            if ($sale->getAcheteur()) {
                $notifBuyer = new Notification();
                $notifBuyer->setUser($sale->getAcheteur());
                $notifBuyer->setSale($sale);
                $notifBuyer->setType(Notification::TYPE_PURCHASED);
                $entityManager->persist($notifBuyer);
            }
            if ($sale->getVendeur()) {
                $notifVendor = new Notification();
                $notifVendor->setUser($sale->getVendeur());
                $notifVendor->setSale($sale);
                $notifVendor->setType(Notification::TYPE_SOLD);
                $entityManager->persist($notifVendor);
            }
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Paiement confirmé avec succès',
                'invoice_path' => $invoicePath,
            ]);
        } catch (\Throwable $e) {
            error_log('Erreur confirmation paiement: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $message = $e->getMessage();
            if (str_contains($message, 'No such payment_intent') || str_contains($message, 'Stripe')) {
                $message = 'Clé Stripe invalide ou Payment Intent introuvable. Vérifiez STRIPE_SECRET_KEY dans .env.';
            }
            return new JsonResponse([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        PaymentService $paymentService,
        SaleRepository $saleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        // TODO: Vérifier la signature Stripe
        // Pour l'instant, on traite les événements de base

        $event = json_decode($payload, true);

        if ($event['type'] === 'payment_intent.succeeded') {
            $paymentIntent = $event['data']['object'];
            $saleId = $paymentIntent['metadata']['sale_id'] ?? null;

            if ($saleId) {
                $sale = $saleRepository->find($saleId);
                if ($sale) {
                    $sale->setStatus(\App\Enum\SaleStatus::PAYER);
                    $entityManager->flush();
                }
            }
        }

        return new Response('OK', Response::HTTP_OK);
    }
}

