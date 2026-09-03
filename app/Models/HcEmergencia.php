<?php

namespace App\Models;

use App\Enums\ColorTriaje;
use App\Enums\CondicionEgreso;
use App\Enums\DestinoDentroIpress;
use App\Enums\DestinoFueraIpress;
use App\Enums\EstadoAtencion;
use App\Enums\EstadoFirma;
use App\Enums\FuenteAtencion;
use App\Enums\SiNo;
use App\Enums\SiNoNoSabe;
use App\Enums\TopicoAtencion;
use App\Enums\UnidadTiempoEnfermedad;
use App\Models\Concerns\TieneAuditoriaBasica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HcEmergencia extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TieneAuditoriaBasica;

    protected $table = 'hc_emergencia';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_hora_inicio_episodio' => 'datetime',
            'fecha_hora_atencion' => 'datetime',
            'fecha_hora_recepcion' => 'datetime',
            'fecha_hora_triaje' => 'datetime',
            'reevaluacion_programada' => 'datetime',
            'fecha_hora_anamnesis' => 'datetime',
            'shock_trauma_fecha_hora' => 'datetime',
            'fecha_hora_firma' => 'datetime',
            'fecha_hora_egreso' => 'datetime',
            'topico' => TopicoAtencion::class,
            'sin_identificacion' => SiNo::class,
            'fuente' => FuenteAtencion::class,
            'color_triaje' => ColorTriaje::class,
            'triaje' => 'boolean',
            'unidad_tiempo_enfermedad' => UnidadTiempoEnfermedad::class,
            'presenta_ram' => SiNoNoSabe::class,
            'presenta_alergias' => SiNoNoSabe::class,
            'en_shock_trauma' => SiNo::class,
            'estado_firma' => EstadoFirma::class,
            'condicion_egreso' => CondicionEgreso::class,
            'destino_dentro_ipress' => DestinoDentroIpress::class,
            'destino_fuera_ipress' => DestinoFueraIpress::class,
            'estado_atencion' => EstadoAtencion::class,
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function profesionalTriaje(): BelongsTo
    {
        return $this->belongsTo(ProfesionalSalud::class, 'profesional_triaje_id');
    }

    public function profesionalResponsable(): BelongsTo
    {
        return $this->belongsTo(ProfesionalSalud::class, 'profesional_salud_id');
    }

    public function signosVitalesTriaje(): HasMany
    {
        return $this->hasMany(TriajeSignoVital::class);
    }

    public function diagnosticosIngreso(): HasMany
    {
        return $this->hasMany(IngresoDiagnostico::class);
    }

    public function solicitudesExamenes(): HasMany
    {
        return $this->hasMany(SolicitudExamen::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }

    public function prescripciones(): HasMany
    {
        return $this->hasMany(Prescripcion::class);
    }

    public function procedimientosRealizados(): HasMany
    {
        return $this->hasMany(ProcedimientoRealizado::class);
    }

    public function evoluciones(): HasMany
    {
        return $this->hasMany(Evolucion::class);
    }

    public function notasEnfermeria(): HasMany
    {
        return $this->hasMany(EnfermeriaNota::class);
    }

    public function interconsultas(): HasMany
    {
        return $this->hasMany(Interconsulta::class);
    }

    public function transferencias(): HasMany
    {
        return $this->hasMany(Transferencia::class);
    }

    public function altaMedicamentos(): HasMany
    {
        return $this->hasMany(AltaMedicamento::class);
    }

    public function documentosAdjuntos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class);
    }

    public function profesionalEgreso(): BelongsTo
    {
        return $this->belongsTo(ProfesionalSalud::class, 'profesional_egreso_id');
    }

    public function referencia(): HasOne
    {
        return $this->hasOne(HcReferencia::class);
    }

    public function diagnosticosEgreso(): HasMany
    {
        return $this->hasMany(EgresoDiagnostico::class);
    }
}
