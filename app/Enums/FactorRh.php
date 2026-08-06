<?php

namespace App\Enums;

enum FactorRh: string
{
    case Positivo = '+';
    case Negativo = '-';
    case NoConocido = 'No conocido';
}
