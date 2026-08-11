<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfesionalSalud extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'profesionales_salud';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCatalogo::class,
        ];
    }

    public function triajes(): HasMany
    {
        return $this->hasMany(HcEmergencia::class, 'profesional_triaje_id');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function atencionesResponsables(): HasMany
    {
        return $this->hasMany(HcEmergencia::class, 'profesional_salud_id');
    }
}
