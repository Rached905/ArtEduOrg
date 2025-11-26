<?php

namespace App\Enum;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case USER = 'VENDEUR';
    case AGENT = 'CLIENT';
}
