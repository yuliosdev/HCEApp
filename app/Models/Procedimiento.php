<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procedimiento extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $guarded = ['id'];

    /**
     * Procedimientos registrados dentro de episodios de emergencia.
     */
    public function procedimientosRealizados(): HasMany
    {
        return $this->hasMany(ProcedimientoRealizado::class);
    }
}
