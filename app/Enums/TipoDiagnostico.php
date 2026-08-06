<?php

namespace App\Enums;

enum TipoDiagnostico: string
{
    case Presuntivo = 'P';
    case Definitivo = 'D';
    case Repetido = 'R';
}
