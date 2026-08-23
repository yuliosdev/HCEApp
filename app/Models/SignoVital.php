<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SignoVital extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'signos_vitales';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'rango_inicial' => 'decimal:2',
            'rango_final' => 'decimal:2',
        ];
    }

    /**
     * Valores tomados durante el triaje.
     */
    public function valoresTriaje(): HasMany
    {
        return $this->hasMany(TriajeSignoVital::class);
    }
}
