<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicamentoHabitual extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'medicamentos_habituales';

    protected $guarded = ['id'];

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }
}
