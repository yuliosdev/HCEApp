<?php

namespace App\Enums;

enum TipoDocumentoAdjunto: string
{
    case Imagen = 'Imagen';
    case Pdf = 'PDF';
    case Otro = 'Otro';
}
