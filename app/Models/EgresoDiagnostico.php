<?php

namespace App\Models;

use App\Enums\ClasificacionDiagnosticoEgreso;
use App\Enums\TipoDiagnostico;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgresoDiagnostico extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'clasificacion' => ClasificacionDiagnosticoEgreso::class,
            'tipo_diagnostico' => TipoDiagnostico::class,
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function diagnosticoCie10(): BelongsTo
    {
        return $this->belongsTo(DiagnosticoCie10::class);
    }
}
