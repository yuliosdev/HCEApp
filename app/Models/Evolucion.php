<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evolucion extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'evoluciones';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function profesionalSalud(): BelongsTo
    {
        return $this->belongsTo(ProfesionalSalud::class);
    }
}
