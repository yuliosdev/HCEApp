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

    /**
     * Valores tomados durante el triaje.
     */
    public function valoresTriaje(): HasMany
    {
        return $this->hasMany(TriajeSignoVital::class);
    }
}
