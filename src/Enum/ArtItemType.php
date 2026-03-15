<?php
namespace App\Enum;

enum ArtItemType: string {
    case PEINTURE = 'peinture';
    case PHOTOGRAPHIE = 'photographie';
    case SCULPTURE = 'sculpture';
    case MUSIQUE = 'musique';
    case VIDEO = 'video';
    case LIVRE = 'livre';
    case DIGITAL_ART = 'digital art';
    case AUTRE = 'autre';

}

