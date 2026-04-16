<?php

namespace App\Entity;

enum EtatPartie: string
{
    case BROUILLON = 'brouillon';
    case EN_COURS = 'en_cours';
    case EN_ATTENTE_DECISION = 'en_attente_decision';
    case TERMINEE = 'terminee';
}