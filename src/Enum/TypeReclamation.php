<?php

namespace App\Enum;

enum TypeReclamation: string
{
    case EVENEMENT = 'evenement';
    case COMMANDE = 'commande';
    case PAIEMENT = 'paiement';
    case TECHNIQUE = 'technique';
    case AUTRE = 'autre';
}