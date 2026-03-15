<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Pour les requêtes POST vers /payment/confirm, renvoie toujours du JSON en cas d'erreur,
 * afin que le front (fetch) reçoive un message exploitable au lieu d'une page HTML.
 */
class PaymentConfirmExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 128],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getMethod() !== 'POST') {
            return;
        }

        $path = $request->getPathInfo();
        if (strpos($path, '/payment/confirm') === false) {
            return;
        }

        $exception = $event->getThrowable();
        $message = $exception->getMessage();

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        if (str_contains($message, 'Stripe') || str_contains($message, 'payment_intent')) {
            $message = 'Erreur Stripe. Vérifiez STRIPE_SECRET_KEY dans .env (clé de test sk_test_...).';
        }

        $event->setResponse(new JsonResponse([
            'error' => $message,
        ], $statusCode));
    }
}
