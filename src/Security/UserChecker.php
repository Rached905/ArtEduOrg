<?php

namespace App\Security;

use App\Entity\Users;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        // ✅ Bloquer AVANT l'authentification si isActive = 0
        if (!$user->getIsActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé. Veuillez contacter nous par email "kacemi396@gmail.com".'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Users) {
            return;
        }

        if (!$user->getIsActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé.'
            );
        }
    }
}