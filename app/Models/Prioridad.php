<?php

namespace App\Models;

use App\Enums\ColorTriaje;
use App\Enums\EstadoCatalogo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prioridad extends Model
{
    use HasFactory;

    protected $table = 'prioridades';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'color' => ColorTriaje::class,
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
