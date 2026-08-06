<?php

namespace App\Http\Controllers;

use App\Enums\EstadoAtencion;
use App\Models\HcEmergencia;
use App\Models\Paciente;
use App\Models\Prioridad;
use App\Models\ProfesionalSalud;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard.index', [
            'episodiosAbiertos' => HcEmergencia::where('estado_atencion', EstadoAtencion::Abierta->value)->count(),
            'episodiosCerrados' => HcEmergencia::where('estado_atencion', EstadoAtencion::Cerrada->value)->count(),
            'pacientesRegistrados' => Paciente::count(),
            'profesionalesActivos' => ProfesionalSalud::count(),
            'prioridades' => Prioridad::query()
                ->orderBy('orden')
                ->get(['id', 'prioridad', 'color', 'orden']),
        ]);
    }
}
