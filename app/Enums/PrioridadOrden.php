<?php

namespace App\Enums;

enum PrioridadOrden: string
{
    case Urgente = 'Urgente';
    case Emergencia = 'Emergencia';
    case Inmediata = 'Inmediata';
}
