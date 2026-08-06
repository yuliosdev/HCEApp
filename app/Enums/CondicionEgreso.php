<?php

namespace App\Enums;

enum CondicionEgreso: string
{
    case Curado = '1.CURADO';
    case Mejorado = '2.MEJORADO';
    case Controlado = '3.CONTROLADO';
    case Empeorado = '4.EMPEORADO';
    case Fallecido = '5.FALLECIDO';
    case Desercion = '6.DESERCION';
}
