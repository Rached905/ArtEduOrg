<?php

namespace App\EventSubscriber;

use App\Repository\ReclamationRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ReclamationStatsSubscriber implements EventSubscriberInterface
{
    private ReclamationRepository $reclamationRepository;
    private Environment $twig;

    public function __construct(ReclamationRepository $reclamationRepository, Environment $twig)
    {
        $this->reclamationRepository = $reclamationRepository;
        $this->twig = $twig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $reclamations = $this->reclamationRepository->findAll();
        
        $stats = [
            'total' => count($reclamations),
            'en_attente' => 0,
            'en_cours' => 0,
            'resolue' => 0,
            'rejetee' => 0
        ];
        
        foreach ($reclamations as $reclamation) {
            $status = $reclamation->getStatusReclamation()->value;
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        // Rendre les stats disponibles globalement dans Twig
        $this->twig->addGlobal('reclamation_stats', $stats);
    }
}