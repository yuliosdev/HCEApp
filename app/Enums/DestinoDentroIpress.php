<?php

namespace App\Enums;

enum DestinoDentroIpress: string
{
    case Hospitalizacion = 'Hospitalizacion';
    case Uci = 'UCI';
    case CentroQuirurgico = 'Centro quirurgico';
    case CentroObstetrico = 'Centro obstetrico';
    case OtraUpss = 'Otra UPSS';
    case Desercion = '6.DESERCION';
}
