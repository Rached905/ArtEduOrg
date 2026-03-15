<?php
namespace App\Enum;

enum SaleStatus: string {
    // Anciens statuts (rétrocompatibilité)
    case EN_ATTENTE = 'en attente';
    case PAYE = 'paye';
    case ANNULE = 'annule';
    
    // Nouveaux statuts pour le workflow avancé
    case DISPONIBLE = 'disponible';
    case EN_PROGRESS = 'en progress';
    case VENDUE = 'vendue';
    case REFUSER = 'refuser';
    case PAYEMENT_EN_COURS = 'payement en cours';
    case PAYER = 'payer';
}

