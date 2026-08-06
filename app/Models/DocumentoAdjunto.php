<?php

namespace App\Models;

use App\Enums\TipoDocumentoAdjunto;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoAdjunto extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'documentos_adjuntos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoDocumentoAdjunto::class,
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }
}
