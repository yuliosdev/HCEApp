<?php

namespace App\Enums;

enum EstadoSolicitudExamen: string
{
    case Solicitado = 'Solicitado';
    case EnProceso = 'En proceso';
    case Terminado = 'Terminado';
}
