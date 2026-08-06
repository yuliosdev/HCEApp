<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfesionalSalud extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'profesionales_salud';

    protected $guarded = ['id'];

    public function triajes(): HasMany
    {
        return $this->hasMany(HcEmergencia::class, 'profesional_triaje_id');
    }

    public function atencionesResponsables(): HasMany
    {
        return $this->hasMany(HcEmergencia::class, 'profesional_salud_id');
    }
}
