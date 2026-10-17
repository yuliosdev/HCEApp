<?php

namespace App\Models;

use App\Enums\EstadoSolicitudExamen;
use App\Enums\PrioridadOrden;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orden extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'ordenes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora_solicitud' => 'datetime',
            'prioridad' => PrioridadOrden::class,
            'estado' => EstadoSolicitudExamen::class,
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function tipoOrden(): BelongsTo
    {
        return $this->belongsTo(TipoOrden::class);
    }

    public function procedimiento(): BelongsTo
    {
        return $this->belongsTo(Procedimiento::class);
    }

    public function profesionalSalud(): BelongsTo
    {
        return $this->belongsTo(ProfesionalSalud::class);
    }

    public function documentosAdjuntos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class);
    }
}
