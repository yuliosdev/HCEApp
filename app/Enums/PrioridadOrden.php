<?php

namespace App\Enums;

enum PrioridadOrden: string
{
    case Regular = 'Regular';
    case Urgente = 'Urgente';
    case Emergencia = 'Emergencia';
    case Inmediata = 'Inmediata';
}
