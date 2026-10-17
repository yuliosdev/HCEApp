<?php

namespace App\Enums;

enum EstadoSolicitudExamen: string
{
    case Solicitado = 'Solicitado';
    case Registrado = 'Registrado';
    case EnProceso = 'En proceso';
    case Terminado = 'Terminado';
    case Pagado = 'Pagado';
}
