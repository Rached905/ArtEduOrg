<?php

namespace App\Twig;

use App\Repository\SponsorRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SponsorExtension extends AbstractExtension
{
    public function __construct(
        private SponsorRepository $sponsorRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_global_sponsors', [$this, 'getGlobalSponsors']),
            new TwigFunction('get_event_sponsors', [$this, 'getEventSponsors']),
        ];
    }

    /**
     * Récupère les sponsors globaux de la plateforme (avec logo)
     * Ce sont les sponsors qui doivent être visibles dans le footer de toutes les pages
     */
    public function getGlobalSponsors(): array
    {
        try {
            $allSponsors = $this->sponsorRepository->findAll();
            $globalSponsors = [];
            
            foreach ($allSponsors as $sponsor) {
                // Un sponsor de plateforme est un sponsor qui a un logo
                // On affiche tous les sponsors avec logo dans le footer
                if ($sponsor->getLogo()) {
                    $globalSponsors[] = $sponsor;
                }
            }
            
            return $globalSponsors;
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, retourner un tableau vide
            return [];
        }
    }

    /**
     * Récupère les sponsors d'un événement spécifique
     */
    public function getEventSponsors($event): array
    {
        try {
            if (!$event || !method_exists($event, 'getSponsors')) {
                return [];
            }
            
            return $event->getSponsors()->toArray();
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, retourner un tableau vide
            return [];
        }
    }
}

