<?php

namespace App\Models;

use App\Enums\ColorTriaje;
use App\Enums\EstadoCatalogo;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prioridad extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'prioridades';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'color' => ColorTriaje::class,
            'tiempo_atencion' => 'integer',
            'estado' => EstadoCatalogo::class,
        ];
    }

    /**
     * Episodios de emergencia clasificados con esta prioridad.
     */
    public function hcEmergencias(): HasMany
    {
        return $this->hasMany(HcEmergencia::class);
    }
}
