<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicamento extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $guarded = ['id'];

    public function prescripciones(): HasMany
    {
        return $this->hasMany(Prescripcion::class);
    }

    public function medicamentosAlta(): HasMany
    {
        return $this->hasMany(AltaMedicamento::class);
    }
}
