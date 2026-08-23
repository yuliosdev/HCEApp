<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ups extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'ups';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function hospitalizacionesPrevias(): HasMany
    {
        return $this->hasMany(HospitalizacionPrevia::class);
    }

    public function referenciasOrigen(): HasMany
    {
        return $this->hasMany(HcReferencia::class, 'ups_origen_id');
    }

    public function referenciasDestino(): HasMany
    {
        return $this->hasMany(HcReferencia::class, 'ups_destino_id');
    }
}
