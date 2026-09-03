<?php

namespace App\Enums;

enum DestinoFueraIpress: string
{
    case No = 'No';
    case TransferenciaOtraIpress = 'Transferencia a otra IPRESS';
    case AltaVoluntaria = 'Alta voluntaria';
    case AltaMedica = 'Alta médica';
    case Fallecido = 'Fallecido';
    case Desercion = 'Deserción';
    case Otro = 'Otro';
}
