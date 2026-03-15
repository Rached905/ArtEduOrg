<?php
namespace App\Enum;

enum SaleType: string {
    case VENTE = 'vente';
    case TICKET = 'ticket';
    case ECHANGE = 'echange';
}

