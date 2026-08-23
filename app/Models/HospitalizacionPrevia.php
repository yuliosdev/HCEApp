<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalizacionPrevia extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'hospitalizaciones_previas';

    protected $guarded = ['id'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function ups(): BelongsTo
    {
        return $this->belongsTo(Ups::class);
    }

    public function diagnosticoCie10(): BelongsTo
    {
        return $this->belongsTo(DiagnosticoCie10::class);
    }
}
