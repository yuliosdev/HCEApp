<?php

namespace App\Enums;

enum UnidadTiempoEnfermedad: string
{
    case Min = 'Min';
    case Hrs = 'Hrs';
    case Dias = 'Dias';
    case Meses = 'Meses';
    case Anos = 'Anos';
}
