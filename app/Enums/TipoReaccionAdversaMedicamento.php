<?php

namespace App\Enums;

enum TipoReaccionAdversaMedicamento: string
{
    case ReaccionAdversa = '1 Reaccion adversa';
    case ErrorMedicacion = '2 Error de medicacion';
    case ProblemaCalidad = '3 Problema de calidad';
    case Otro = '4 Otro';
}
