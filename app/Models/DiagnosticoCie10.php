<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Enums\GeneroCie10;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosticoCie10 extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'diagnosticos_cie10';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'genero' => GeneroCie10::class,
            'estado' => EstadoCatalogo::class,
        ];
    }

    public function antecedentesPatologicos(): HasMany
    {
        return $this->hasMany(AntecedentePatologico::class);
    }

    public function diagnosticosIngreso(): HasMany
    {
        return $this->hasMany(IngresoDiagnostico::class);
    }

    public function diagnosticosEgreso(): HasMany
    {
        return $this->hasMany(EgresoDiagnostico::class);
    }
}
