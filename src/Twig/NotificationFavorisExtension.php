<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use App\Repository\SaleFavoriteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationFavorisExtension extends AbstractExtension
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private SaleFavoriteRepository $saleFavoriteRepository,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notification_count', [$this, 'notificationCount']),
            new TwigFunction('notification_list', [$this, 'notificationList']),
            new TwigFunction('favoris_count', [$this, 'favorisCount']),
        ];
    }

    public function notificationCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return 0;
        }
        return $this->notificationRepository->countByUser($user);
    }

    /**
     * @return \App\Entity\Notification[]
     */
    public function notificationList(int $limit = 10): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return [];
        }
        return $this->notificationRepository->findByUserOrderByCreatedDesc($user, $limit);
    }

    public function favorisCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Users) {
            return 0;
        }
        return $this->saleFavoriteRepository->countByUser($user);
    }
}
