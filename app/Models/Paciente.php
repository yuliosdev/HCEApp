<?php

namespace App\Models;

use App\Enums\FactorRh;
use App\Enums\GrupoSanguineo;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'grupo_sanguineo' => GrupoSanguineo::class,
            'factor_rh' => FactorRh::class,
        ];
    }

    public function hcEmergencias(): HasMany
    {
        return $this->hasMany(HcEmergencia::class);
    }

    public function alergias(): HasMany
    {
        return $this->hasMany(PacienteAlergia::class);
    }

    public function antecedentesPatologicos(): HasMany
    {
        return $this->hasMany(AntecedentePatologico::class);
    }

    public function reaccionesAdversasMedicamentos(): HasMany
    {
        return $this->hasMany(ReaccionAdversaMedicamento::class);
    }

    public function antecedentesFamiliares(): HasMany
    {
        return $this->hasMany(AntecedenteFamiliar::class);
    }

    public function cirugiasPrevias(): HasMany
    {
        return $this->hasMany(CirugiaPrevia::class);
    }

    public function hospitalizacionesPrevias(): HasMany
    {
        return $this->hasMany(HospitalizacionPrevia::class);
    }

    public function medicamentosHabituales(): HasMany
    {
        return $this->hasMany(MedicamentoHabitual::class);
    }
}
