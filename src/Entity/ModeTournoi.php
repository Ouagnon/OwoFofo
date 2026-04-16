<?php

namespace App\Entity;

enum ModeTournoi: string
{
    case LIBRE = 'libre';
    case THEME_VS_THEME = 'theme_vs_theme';
}
