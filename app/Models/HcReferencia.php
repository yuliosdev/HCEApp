<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcReferencia extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'hc_referencias';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora_solicitud' => 'datetime',
            'fecha_hora_respuesta' => 'datetime',
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function upsOrigen(): BelongsTo
    {
        return $this->belongsTo(Ups::class, 'ups_origen_id');
    }

    public function upsDestino(): BelongsTo
    {
        return $this->belongsTo(Ups::class, 'ups_destino_id');
    }
}
