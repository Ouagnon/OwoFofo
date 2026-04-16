<?php

namespace App\Entity;

enum EtatManche: string
{
    case EN_PREPARATION = 'en_preparation';
    case EN_COURS = 'en_cours';
    case EN_ATTENTE_DECISION = 'en_attente_decision';
    case TERMINEE = 'terminee';
}
