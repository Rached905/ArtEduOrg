<?php

namespace App\Entity\Enum;

enum EventStatus: string
{
    case PROGRAMME = 'PROGRAMME';
    case EN_COURS  = 'EN_COURS';
    case TERMINE   = 'TERMINE';
    case ANNULE    = 'ANNULE';
}


