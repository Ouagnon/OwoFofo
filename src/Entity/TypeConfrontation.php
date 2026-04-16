<?php

namespace App\Entity;

enum TypeConfrontation: string
{
    case LIBRE = 'libre';
    case INTER_THEME = 'inter_theme';
    case INTRA_THEME = 'intra_theme';
}
