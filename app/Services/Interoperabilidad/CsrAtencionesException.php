<?php

namespace App\Services\Interoperabilidad;

use RuntimeException;

class CsrAtencionesException extends RuntimeException
{
    public function __construct(
        public readonly string $tipo,
        public readonly ?int $status = null,
    ) {
        parent::__construct($tipo);
    }

    public function mensajeUsuario(): string
    {
        return match ($this->tipo) {
            'not_found' => 'No se encontraron atenciones ambulatorias para el paciente.',
            'unauthorized' => 'No se pudo validar el acceso a las atenciones ambulatorias.',
            default => 'No se pudieron cargar las atenciones ambulatorias en este momento.',
        };
    }
}
