<?php

namespace App\Models;

use App\Enums\GravedadReaccionAdversaMedicamento;
use App\Enums\TipoReaccionAdversaMedicamento;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReaccionAdversaMedicamento extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'reacciones_adversas_medicamentos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tipo' => TipoReaccionAdversaMedicamento::class,
            'gravedad' => GravedadReaccionAdversaMedicamento::class,
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
