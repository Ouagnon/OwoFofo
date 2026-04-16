<?php

namespace App\Entity;

enum ActionImpair: string
{
    case EN_ATTENTE = 'en_attente';
    case REMPLACER = 'remplacer';
    case ELIMINER = 'eliminer';
}
