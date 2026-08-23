<?php

namespace App\Models;

use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Glasgow extends Model
{
    use HasFactory;
    use TieneAuditoriaBasica;

    protected $table = 'glasgow';

    protected $guarded = ['id'];
}
