<?php

namespace App\Models;

use App\Enums\EstadoSolicitudExamen;
use App\Enums\PrioridadSolicitudExamen;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudExamen extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'solicitud_examenes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora_solicitud' => 'datetime',
            'prioridad' => PrioridadSolicitudExamen::class,
            'estado' => EstadoSolicitudExamen::class,
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }
}
