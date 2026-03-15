<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class NotificationController extends AbstractController
{
    #[Route('/client/notifications', name: 'app_client_notifications', methods: ['GET'])]
    public function clientIndex(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->redirectToRoute('app_client');
        }
        $notifications = $notificationRepository->findByUserOrderByCreatedDesc($user);
        return $this->render('client/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/vendeur/notifications', name: 'app_vendeur_notifications', methods: ['GET'])]
    public function vendeurIndex(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return $this->redirectToRoute('app_vendeur');
        }
        $notifications = $notificationRepository->findByUserOrderByCreatedDesc($user);
        return $this->render('vendeur/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/notifications/{id}/read', name: 'app_notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, EntityManagerInterface $em, Request $request, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('notification_read', $token))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\Users || $notification->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }
        $notification->markAsRead();
        $em->flush();
        $referer = $request->headers->get('Referer', $this->generateUrl('app_client_notifications'));
        return $this->redirect($referer);
    }
}
