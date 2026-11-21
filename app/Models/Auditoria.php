<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auditoria extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'auditoria';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio' => 'datetime',
            'fecha_hora_fin' => 'datetime',
            'datos_incompletos' => 'array',
        ];
    }

    public function hcEmergencia(): BelongsTo
    {
        return $this->belongsTo(HcEmergencia::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
