<?php

namespace App\Enum;

enum StatusReclamation: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_COURS = 'en_cours';
    case RESOLUE = 'resolue';
    case REJETEE = 'rejete';
}
