<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescripcion extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'prescripciones';

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

    public function medicamentoCatalogo(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }
}
