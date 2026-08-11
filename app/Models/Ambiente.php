<?php

namespace App\Models;

use App\Enums\EstadoCatalogo;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ambiente extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'ambientes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCatalogo::class,
        ];
    }
}
