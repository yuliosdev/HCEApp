<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriajeSignoVital extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'triaje_signos_vitales';

    protected $guarded = ['id'];

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function signoVital(): BelongsTo
    {
        return $this->belongsTo(SignoVital::class);
    }
}
