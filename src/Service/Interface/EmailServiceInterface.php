<?php

namespace App\Service\Interface;

/**
 * Interface pour les services d'envoi d'email
 * Permet de découpler les services et d'éviter les conflits entre modules
 */
interface EmailServiceInterface
{
    /**
     * Envoie un email générique
     *
     * @param string $to Adresse email du destinataire
     * @param string $subject Sujet de l'email
     * @param string $template Template Twig à utiliser
     * @param array $context Variables à passer au template
     * @param array $attachments Chemins vers les fichiers à attacher
     * @return void
     * @throws \Exception En cas d'erreur d'envoi
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = []
    ): void;
}

