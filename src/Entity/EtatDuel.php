<?php

namespace App\Entity;

enum EtatDuel: string
{
    case A_JOUER = 'a_jouer';
    case TERMINE = 'termine';
}
