<?php

namespace App\Entity;

enum TypeManche: string
{
    case M64 = 'm64';
    case M32 = 'm32';
    case M16 = 'm16';
    case M8 = 'm8';
    case M4 = 'm4';
    case M2 = 'm2';
}