<?php

namespace App\Enums;

enum DestinoDentroIpress: string
{
    case No = 'No';
    case Hospitalizacion = 'Hospitalización';
    case Uci = 'UCI';
    case CentroQuirurgico = 'Centro quirúrgico';
    case CentroObstetrico = 'Centro obstétrico';
    case OtraUpss = 'Otra UPSS';
    case Otro = 'Otro';
}
