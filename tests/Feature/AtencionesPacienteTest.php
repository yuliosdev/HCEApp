<?php

namespace Tests\Feature;

use App\Enums\ClasificacionDiagnosticoEgreso;
use App\Enums\ColorTriaje;
use App\Enums\CondicionEgreso;
use App\Enums\DestinoDentroIpress;
use App\Enums\DestinoFueraIpress;
use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Enums\GravedadReaccionAdversaMedicamento;
use App\Enums\PrioridadOrden;
use App\Enums\PrioridadSolicitudExamen;
use App\Enums\TipoDiagnostico;
use App\Enums\TipoDocumentoAdjunto;
use App\Enums\TipoReaccionAdversaMedicamento;
use App\Enums\TopicoAtencion;
use App\Livewire\HistoriaClinica\AtencionesPacientePanel;
use App\Livewire\HistoriaClinica\HcEmergenciaPanel;
use App\Livewire\HistoriaClinica\HistoriaClinicaPaciente;
use App\Livewire\HistoriaClinica\HistoriasAbiertasPanel;
use App\Livewire\HistoriaClinica\PacienteDashboard;
use App\Models\Alergia;
use App\Models\AntecedentePatologico;
use App\Models\DiagnosticoCie10;
use App\Models\DocumentoAdjunto;
use App\Models\EgresoDiagnostico;
use App\Models\Especialidad;
use App\Models\Examen;
use App\Models\Glasgow;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Medicamento;
use App\Models\Orden;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\Prioridad;
use App\Models\Procedimiento;
use App\Models\ProfesionalSalud;
use App\Models\ReaccionAdversaMedicamento;
use App\Models\SignoVital;
use App\Models\TipoOrden;
use App\Models\TriajeSignoVital;
use App\Models\Ups;
use App\Models\User;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AtencionesPacienteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_sin_permiso_no_puede_ver_atenciones_del_paciente(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create(['activo' => true]);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.index'))
            ->assertForbidden();
    }

    public function test_usuario_con_permiso_visualiza_busqueda_de_pacientes(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.index'))
            ->assertOk()
            ->assertSee('Busqueda de Pacientes')
            ->assertSee('Buscar paciente')
            ->assertSee('Utilice la busqueda superior para encontrar pacientes.');
    }

    public function test_busqueda_presenta_coincidencias_en_cards_de_pacientes(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Maria Rodriguez',
            'sexo' => 'Femenino',
            'fecha_nacimiento' => now()->subYears(68)->format('Y-m-d'),
            'tipo_documento' => 'DNI',
            'numero_documento' => '45283910',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'fecha_hora_atencion' => '2026-08-05 10:30:00',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(AtencionesPacientePanel::class)
            ->set('busqueda', 'Maria')
            ->call('buscar')
            ->assertSee('Maria Rodriguez')
            ->assertSee('68 anos')
            ->assertSee('45283910')
            ->assertSee('05/08/2026')
            ->assertSee('Femenino')
            ->assertSee('Ver historial')
            ->assertSee('patient-result-card', false);
    }

    public function test_boton_ver_historial_redirige_al_dashboard_del_paciente(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Carlos Mendoza',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(AtencionesPacientePanel::class)
            ->call('prepararHistorial', $paciente->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.dashboard', $paciente));
    }

    public function test_dashboard_del_paciente_muestra_datos_y_boton_de_historia_clinica(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Robert J. Oppenheimer',
            'sexo' => 'Masculino',
            'fecha_nacimiento' => '1954-04-12',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.dashboard', $paciente))
            ->assertOk()
            ->assertSee('Dashboard del paciente')
            ->assertSee('Robert J. Oppenheimer')
            ->assertSee('Ver Historia Clinica')
            ->assertSee('ALERGIA SEVERA')
            ->assertSee('REACCION ADVERSA')
            ->assertSee('Medicamentos habituales')
            ->assertSee('Tendencias de Signos Vitales');
    }

    public function test_drawer_del_dashboard_registra_alergia_del_paciente(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $alergia = Alergia::query()->create([
            'nombre' => 'Penicilina',
            'tipo' => 'Medicamento',
            'descripcion' => 'Alergia a betalactamicos',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(PacienteDashboard::class, ['paciente' => $paciente])
            ->call('abrirDrawer', 'alergias')
            ->set('formulario.alergia_id', (string) $alergia->id)
            ->set('formulario.agente_causal', 'Penicilina')
            ->set('formulario.alergeno', 'Betalactamico')
            ->set('formulario.periodo', '2026')
            ->call('guardarRegistro')
            ->assertSee('Penicilina');

        $this->assertDatabaseHas('paciente_alergias', [
            'paciente_id' => $paciente->id,
            'alergia_id' => $alergia->id,
            'agente_causal' => 'Penicilina',
        ]);
    }

    public function test_dashboard_del_paciente_muestra_todas_las_alergias_y_reacciones_adversas(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $alergiaPenicilina = Alergia::query()->create([
            'nombre' => 'Penicilina',
            'tipo' => 'Medicamento',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $alergiaLatex = Alergia::query()->create([
            'nombre' => 'Latex',
            'tipo' => 'Material',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        PacienteAlergia::query()->create([
            'paciente_id' => $paciente->id,
            'alergia_id' => $alergiaPenicilina->id,
            'agente_causal' => 'Antibiotico',
            'periodo' => '2024',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        PacienteAlergia::query()->create([
            'paciente_id' => $paciente->id,
            'alergia_id' => $alergiaLatex->id,
            'alergeno' => 'Guantes',
            'periodo' => '2025',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        ReaccionAdversaMedicamento::query()->create([
            'paciente_id' => $paciente->id,
            'descripcion' => 'Tos seca persistente',
            'tipo' => TipoReaccionAdversaMedicamento::ReaccionAdversa,
            'gravedad' => GravedadReaccionAdversaMedicamento::Leve,
            'dci_medicamento' => 'Lisinopril',
            'periodo' => '2023',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        ReaccionAdversaMedicamento::query()->create([
            'paciente_id' => $paciente->id,
            'descripcion' => 'Nauseas intensas',
            'tipo' => TipoReaccionAdversaMedicamento::Otro,
            'gravedad' => GravedadReaccionAdversaMedicamento::Moderado,
            'dci_medicamento' => 'Metformina',
            'periodo' => '2026',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.dashboard', $paciente))
            ->assertOk()
            ->assertSee('Penicilina')
            ->assertSee('Latex')
            ->assertSee('Lisinopril')
            ->assertSee('Metformina')
            ->assertSee('Tos seca persistente')
            ->assertSee('Nauseas intensas');
    }

    public function test_drawer_del_dashboard_registra_medicamento_habitual_del_paciente(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(PacienteDashboard::class, ['paciente' => $paciente])
            ->call('abrirDrawer', 'medicamentos')
            ->assertSee('Medicamentos habituales')
            ->set('formulario.medicacion', 'Losartan 50 mg')
            ->set('formulario.periodo', 'Uso diario')
            ->call('guardarRegistro')
            ->assertHasNoErrors()
            ->assertSee('Losartan 50 mg')
            ->assertSee('Uso diario');

        $this->assertDatabaseHas('medicamentos_habituales', [
            'paciente_id' => $paciente->id,
            'medicacion' => 'Losartan 50 mg',
            'periodo' => 'Uso diario',
        ]);
    }

    public function test_boton_ver_historia_clinica_redirige_a_la_vista_completa(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(PacienteDashboard::class, ['paciente' => $paciente])
            ->call('verHistoriaClinica')
            ->assertRedirect(route('historia-clinica.atenciones-paciente.historia', $paciente));
    }

    public function test_historia_clinica_del_paciente_muestra_resumen_e_historial_paginado(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Garcia, Maria Elena',
            'sexo' => 'Femenino',
            'fecha_nacimiento' => now()->subYears(42)->format('Y-m-d'),
            'grupo_sanguineo' => 'O',
            'factor_rh' => '+',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dra. Carmen Mendoza']);
        $prioridad = Prioridad::query()->create([
            'prioridad' => 'II.URGENCIA MAYOR',
            'color' => 'Naranja',
            'orden' => 2,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $diagnostico = DiagnosticoCie10::query()->create([
            'codigo' => 'I10',
            'nombre' => 'Hipertension esencial',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $alergia = Alergia::query()->create([
            'nombre' => 'Penicilina',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        PacienteAlergia::query()->create([
            'paciente_id' => $paciente->id,
            'alergia_id' => $alergia->id,
            'agente_causal' => 'Antibiotico',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        ReaccionAdversaMedicamento::query()->create([
            'paciente_id' => $paciente->id,
            'descripcion' => 'Tos seca persistente',
            'dci_medicamento' => 'Losartan 50mg',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        AntecedentePatologico::query()->create([
            'paciente_id' => $paciente->id,
            'diagnostico_cie10_id' => $diagnostico->id,
            'denominacion' => 'Hipertension arterial',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $atencionCerrada = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'numero_atencion' => 155248,
            'fecha_hora_atencion' => now()->subDay(),
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'color_triaje' => ColorTriaje::Naranja,
            'triaje' => true,
            'estado_atencion' => EstadoAtencion::Cerrada,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencionAbierta = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'numero_atencion' => 157795,
            'fecha_hora_atencion' => now(),
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'color_triaje' => ColorTriaje::Verde,
            'triaje' => true,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencionCerrada->id,
            'diagnostico_cie10_id' => $diagnostico->id,
            'tipo_diagnostico' => TipoDiagnostico::Definitivo,
            'fecha_hora' => now()->subDay(),
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $pas = SignoVital::query()->create(['descripcion' => 'PAS', 'unidad' => 'mmHg']);
        $pad = SignoVital::query()->create(['descripcion' => 'PAD', 'unidad' => 'mmHg']);
        $fc = SignoVital::query()->create(['descripcion' => 'Frecuencia cardiaca', 'unidad' => 'lpm']);

        foreach ([[$pas->id, '120'], [$pad->id, '80'], [$fc->id, '72']] as [$signoId, $valor]) {
            TriajeSignoVital::query()->create([
                'hc_emergencia_id' => $atencionAbierta->id,
                'signo_vital_id' => $signoId,
                'valor' => $valor,
                'registrado_por' => $usuario->id,
                'actualizado_por' => $usuario->id,
            ]);
        }

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.historia', $paciente))
            ->assertOk()
            ->assertSee('Garcia, Maria Elena')
            ->assertSee('Femenino')
            ->assertSee('42 anos')
            ->assertSee('Penicilina')
            ->assertSee('Losartan 50mg')
            ->assertSee('Hipertension arterial')
            ->assertSee('120/80 mmHg')
            ->assertSee('72 lpm')
            ->assertSee('Historial de Atenciones')
            ->assertSee((string) $atencionAbierta->id)
            ->assertSee('Adm: 157795')
            ->assertSee((string) $atencionCerrada->id)
            ->assertSee('Adm: 155248')
            ->assertSee('Dra. Carmen Mendoza')
            ->assertSee('Hipertension esencial (I10)')
            ->assertSee('Nivel 2')
            ->assertSee('Abierta')
            ->assertSee('Cerrada')
            ->assertSee('editarAtencion('.$atencionAbierta->id.')', false)
            ->assertDontSee('editarAtencion('.$atencionCerrada->id.')', false)
            ->assertSee('Editar atencion')
            ->assertSee(route('historia-clinica.atenciones-paciente.emergencia.imprimir', [$paciente, $atencionAbierta]), false)
            ->assertSee(route('historia-clinica.atenciones-paciente.emergencia.imprimir', [$paciente, $atencionCerrada]), false)
            ->assertSee('lucide:printer', false)
            ->assertSee('Solo Administrador puede cambiar el estado')
            ->assertDontSee('cambiarEstadoAtencion(', false);
    }

    public function test_administrador_puede_cambiar_estado_de_historia_desde_listado(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $usuario->assignRole('Administrador');
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->assertSee('cambiarEstadoAtencion('.$atencion->id.')', false)
            ->call('cambiarEstadoAtencion', $atencion->id);

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'estado_atencion' => EstadoAtencion::Cerrada->value,
            'actualizado_por' => $usuario->id,
        ]);
    }

    public function test_icono_ticket_genera_pdf_termico_de_historia_clinica_de_emergencia(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Garcia, Maria Elena',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Dr. Roel Carreno Cisneros',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'fecha_hora_atencion' => now(),
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->assertSee(route('historia-clinica.atenciones-paciente.emergencia.ticket', [$paciente, $atencion]), false)
            ->assertSee('Ticket de historia');

        $this->actingAs($usuario)
            ->get(route('historia-clinica.atenciones-paciente.emergencia.ticket', [$paciente, $atencion]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_boton_ver_examenes_laboratorio_consulta_api_externa_en_modo_test(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $atencion = HcEmergencia::factory()->create([
            'numero_atencion' => 391399,
        ]);

        config([
            'services.external_api.url' => 'https://api.csr.net.pe',
            'services.external_api.token' => 'token-test',
        ]);

        Http::fake([
            'https://api.csr.net.pe/api/external/sislab/admisiones/391399/guialab-pdf*' => Http::response('%PDF-1.4 test', 200, [
                'Content-Type' => 'application/pdf',
            ]),
        ]);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => null, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'ordenes')
            ->assertSee('Ver exámenes')
            ->assertSee(route('historia-clinica.emergencia.laboratorio.pdf', $atencion), false);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.emergencia.laboratorio.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.csr.net.pe/api/external/sislab/admisiones/391399/guialab-pdf?origen=test'
            && $request->hasHeader('Authorization', 'Bearer token-test')
            && $request->hasHeader('Accept', 'application/pdf'));
    }

    public function test_panel_historias_abiertas_muestra_todas_las_abiertas_por_defecto(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dra. Guardia Emergencia']);
        $pacienteAbierto = Paciente::factory()->create([
            'nombre' => 'Paciente Abierto Hoy',
            'id_paciente_externo' => 789875,
        ]);
        $pacienteCerrado = Paciente::factory()->create(['nombre' => 'Paciente Cerrado Hoy']);
        $pacienteAnterior = Paciente::factory()->create(['nombre' => 'Paciente Abierto Ayer']);
        $prioridad = Prioridad::query()->create([
            'prioridad' => 'I.EMERGENCIA',
            'color' => ColorTriaje::Rojo,
            'orden' => 1,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $atencionAbierta = HcEmergencia::factory()->create([
            'paciente_id' => $pacienteAbierto->id,
            'fecha_hora_atencion' => now()->setTime(9, 30),
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'color_triaje' => ColorTriaje::Rojo,
            'destino_topico' => 'Shock Trauma',
            'triaje' => true,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        HcEmergencia::factory()->create([
            'paciente_id' => null,
            'alias' => 'Alias Sin Registro',
            'fecha_hora_atencion' => now()->setTime(10, 15),
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        HcEmergencia::factory()->create([
            'paciente_id' => $pacienteCerrado->id,
            'fecha_hora_atencion' => now()->setTime(11, 0),
            'estado_atencion' => EstadoAtencion::Cerrada,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        HcEmergencia::factory()->create([
            'paciente_id' => $pacienteAnterior->id,
            'fecha_hora_atencion' => now()->subDay(),
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HistoriasAbiertasPanel::class)
            ->assertSet('fechaDesde', '')
            ->assertSet('fechaHasta', '')
            ->assertSet('estado', EstadoAtencion::Abierta->value)
            ->assertSet('porPagina', 5)
            ->assertSee('Historial de Atenciones')
            ->assertSee('Crear Nueva Historia')
            ->assertSee('Paciente Abierto Hoy')
            ->assertSee('HC: 789875')
            ->assertSee('Alias Sin Registro')
            ->assertSee('Sin Registro')
            ->assertSee('Shock Trauma')
            ->assertSee('Dra. Guardia Emergencia')
            ->assertSee('Nivel 1')
            ->assertSee(route('historia-clinica.atenciones-paciente.emergencia.imprimir', [$pacienteAbierto, $atencionAbierta]), false)
            ->assertSee('lucide:printer', false)
            ->assertSee(route('historia-clinica.atenciones-paciente.emergencia.ticket', [$pacienteAbierto, $atencionAbierta]), false)
            ->assertDontSee('Paciente Cerrado Hoy')
            ->assertSee('Paciente Abierto Ayer')
            ->set('estado', 'Todos')
            ->assertSee('Paciente Cerrado Hoy')
            ->set('porPagina', 10)
            ->assertSet('porPagina', 10);
    }

    public function test_historias_abiertas_exporta_excel_y_pdf(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create(['nombre' => 'Paciente Exportable']);

        HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'fecha_hora_atencion' => now(),
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $filtros = [
            'desde' => now()->toDateString(),
            'hasta' => now()->toDateString(),
            'estado' => EstadoAtencion::Abierta->value,
        ];

        $this->actingAs($usuario)
            ->get(route('historia-clinica.historias-abiertas.exportar.xlsx', $filtros))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($usuario)
            ->get(route('historia-clinica.historias-abiertas.exportar.pdf', $filtros))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_modal_crear_nueva_historia_exige_profesional_y_paciente_o_alias(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);

        Livewire::actingAs($usuario)
            ->test(HistoriasAbiertasPanel::class)
            ->call('crearNuevaHistoria')
            ->assertSet('modalNuevaHistoriaAbierto', true)
            ->call('crearHistoriaDesdeModal')
            ->assertHasErrors([
                'nuevaHistoriaForm.profesional_salud_id' => 'required',
            ])
            ->assertSee('Debe seleccionar un paciente o ingresar un alias.');
    }

    public function test_modal_crea_historia_con_alias_y_permanece_en_historial_de_atenciones(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Nueva Guardia']);

        Livewire::actingAs($usuario)
            ->test(HistoriasAbiertasPanel::class)
            ->call('crearNuevaHistoria')
            ->set('profesionalBusqueda', 'Nueva Guardia')
            ->call('seleccionarProfesional', $profesional->id)
            ->set('nuevaHistoriaForm.alias', 'Paciente NN Trauma')
            ->set('nuevaHistoriaForm.tipo', 'Médica')
            ->call('crearHistoriaDesdeModal')
            ->assertSet('modalNuevaHistoriaAbierto', false)
            ->assertSet('nuevaHistoriaForm.alias', '')
            ->assertSet('nuevaHistoriaForm.tipo', 'Accidental')
            ->assertNoRedirect()
            ->assertSee('Paciente NN Trauma');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => null,
            'alias' => 'Paciente NN Trauma',
            'profesional_salud_id' => $profesional->id,
            'tipo' => 'Médica',
            'estado_atencion' => EstadoAtencion::Abierta->value,
        ]);
    }

    public function test_ticket_de_historia_con_alias_genera_pdf_sin_paciente(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Nueva Guardia']);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => null,
            'alias' => 'Paciente NN Trauma',
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HistoriasAbiertasPanel::class)
            ->assertSee(route('historia-clinica.emergencia.imprimir', $atencion), false)
            ->assertSee(route('historia-clinica.emergencia.ticket', $atencion), false);

        $this->actingAs($usuario)
            ->get(route('historia-clinica.emergencia.imprimir', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($usuario)
            ->get(route('historia-clinica.emergencia.ticket', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_modal_ver_todos_muestra_todas_las_alergias_de_la_historia_clinica(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        foreach (['Penicilina', 'Latex', 'Ibuprofeno'] as $nombre) {
            $alergia = Alergia::query()->create([
                'nombre' => $nombre,
                'registrado_por' => $usuario->id,
                'actualizado_por' => $usuario->id,
            ]);

            PacienteAlergia::query()->create([
                'paciente_id' => $paciente->id,
                'alergia_id' => $alergia->id,
                'agente_causal' => 'Agente '.$nombre,
                'registrado_por' => $usuario->id,
                'actualizado_por' => $usuario->id,
            ]);
        }

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->assertSee('Ver todos')
            ->call('abrirModalResumen', 'alergias')
            ->assertSee('Alergias del paciente')
            ->assertSee('Penicilina')
            ->assertSee('Latex')
            ->assertSee('Ibuprofeno');
    }

    public function test_nuevo_registro_solo_redirige_para_usuario_medico_vinculado(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('nuevoRegistro')
            ->assertNoRedirect();

        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('nuevoRegistro')
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.nuevo', $paciente));
    }

    public function test_ruta_nuevo_registro_carga_panel_de_emergencia(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Maria Elena Garcia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        $this->actingAs($medico)
            ->get(route('historia-clinica.atenciones-paciente.emergencia.nuevo', $paciente))
            ->assertOk()
            ->assertSee('Maria Elena Garcia')
            ->assertSee('Atencion Abierta')
            ->assertSee('Triaje')
            ->assertSee('Anamnesis');
    }

    public function test_panel_nuevo_registro_crea_hc_emergencia_con_paciente_y_medico(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'nombre' => 'Maria Elena Garcia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $prioridad = Prioridad::query()->create([
            'prioridad' => 'II.URGENCIA MAYOR',
            'color' => 'Naranja',
            'orden' => 2,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->assertSee('Maria Elena Garcia')
            ->assertSee('Dr. Roel Carreno Cisneros')
            ->assertSee('Triaje')
            ->assertSee('Anamnesis')
            ->set('form.prioridad_id', $prioridad->id)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'numero_atencion' => null,
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'estado_atencion' => EstadoAtencion::Abierta->value,
        ]);
    }

    public function test_panel_confirma_y_cancela_cierre_de_atencion_sin_cambiar_estado(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->call('cerrarAtencion')
            ->assertSet('panelConfirmacionCierreAbierto', true)
            ->assertSee('¿Esta seguro de Cerrar la atención?')
            ->call('cancelarCierreAtencion')
            ->assertSet('panelConfirmacionCierreAbierto', false)
            ->assertSee('Cerrar Atención');

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'estado_atencion' => EstadoAtencion::Abierta->value,
        ]);
    }

    public function test_panel_cierra_atencion_guardando_cambios_y_regresa_al_historial(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('form.alias', 'Paciente en observacion corta')
            ->call('cerrarAtencion')
            ->call('confirmarCierreAtencion')
            ->assertHasNoErrors()
            ->assertSet('panelAtencionCerradaAbierto', true)
            ->assertSee('Atención ha sido cerrada')
            ->assertDontSee('Cerrar Atención')
            ->call('aceptarAtencionCerrada')
            ->assertRedirect(route('historia-clinica.atenciones-paciente.historia', $paciente));

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'alias' => 'Paciente en observacion corta',
            'estado_atencion' => EstadoAtencion::Cerrada->value,
        ]);
    }

    public function test_panel_en_modo_edicion_muestra_solo_lectura_si_la_atencion_esta_cerrada(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Cerrada,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSee('Atencion Cerrada')
            ->assertDontSee('Cerrar Atención')
            ->assertDontSee('Guardar');
    }

    public function test_panel_permite_cambiar_profesional_tratante_en_atencion_abierta_y_edicion(): void
    {
        $profesionalActual = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $profesionalNuevo = ProfesionalSalud::factory()->create(['nombres' => 'Dra. Ana Ruiz Mendoza']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesionalActual->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesionalActual->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSee('Cambiar Profesional Tratante')
            ->call('abrirModalCambiarProfesionalTratante')
            ->assertSet('modalProfesionalTratanteAbierto', true)
            ->assertSet('profesionalTratanteId', $profesionalActual->id)
            ->set('profesionalTratanteBusqueda', 'Ana Ruiz')
            ->call('seleccionarProfesionalTratante', $profesionalNuevo->id)
            ->assertSet('profesionalTratanteId', $profesionalNuevo->id)
            ->call('guardarProfesionalTratante')
            ->assertHasNoErrors()
            ->assertSet('modalProfesionalTratanteAbierto', false)
            ->assertSee('Dra. Ana Ruiz Mendoza');

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'profesional_salud_id' => $profesionalNuevo->id,
            'actualizado_por' => $medico->id,
        ]);
    }

    public function test_panel_no_permite_cambiar_profesional_tratante_en_atencion_cerrada(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Cerrada,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertDontSee('Cambiar Profesional Tratante')
            ->call('abrirModalCambiarProfesionalTratante')
            ->assertForbidden();
    }

    public function test_panel_registra_medicamento_del_alta_despues_de_guardar_historia_nueva(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $medicamento = Medicamento::query()->create([
            'descripcion' => 'Ibuprofeno',
            'presentacion' => 'Tableta 400mg',
            'generico' => 'Ibuprofeno',
            'stock' => 25,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->call('guardar')
            ->assertHasNoErrors()
            ->call('seguirEditandoHistoria')
            ->set('tabActiva', 'egresos')
            ->set('atencion', null)
            ->set('atencionId', null)
            ->call('abrirNuevoAltaMedicamento')
            ->set('altaMedicamentoBusqueda', 'Ibuprofeno')
            ->call('seleccionarMedicamentoAlta', $medicamento->id)
            ->set('altaMedicamentoForm.dosis', '1 tableta')
            ->set('altaMedicamentoForm.via', 'Oral')
            ->set('altaMedicamentoForm.cantidad', '5')
            ->set('altaMedicamentoForm.indicaciones', 'Tomar si presenta dolor.')
            ->call('guardarAltaMedicamento')
            ->assertHasNoErrors()
            ->assertSee('Ibuprofeno');

        $atencion = HcEmergencia::query()->where('paciente_id', $paciente->id)->firstOrFail();

        $this->assertDatabaseHas('alta_medicamentos', [
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $medicamento->id,
            'medicamento' => 'Ibuprofeno',
            'presentacion' => 'Tableta 400mg',
            'dosis' => '1 tableta',
            'via' => 'Oral',
            'cantidad' => 5,
            'indicaciones' => 'Tomar si presenta dolor.',
        ]);
    }

    public function test_panel_guarda_triaje_con_prioridad_sin_bloquear_por_topico_vacio(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $prioridad = Prioridad::query()->create([
            'prioridad' => 'III.URGENCIA MENOR',
            'color' => 'Amarillo',
            'orden' => 3,
            'tiempo_atencion' => 60,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->set('tabActiva', 'triaje')
            ->set('form.topico', '')
            ->set('form.prioridad_id', $prioridad->id)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'prioridad_id' => $prioridad->id,
            'topico' => TopicoAtencion::Topico->value,
            'tiempo_espera_calculado' => '60 min',
        ]);
    }

    public function test_panel_normaliza_prioridad_en_texto_y_no_valida_topico_al_seleccionarla(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $prioridad = Prioridad::query()->create([
            'prioridad' => 'I.EMERGENCIA',
            'color' => 'Rojo',
            'orden' => 1,
            'tiempo_atencion' => 0,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->set('tabActiva', 'triaje')
            ->set('form.topico', '')
            ->set('form.prioridad_id', 'Nivel 1')
            ->call('guardar')
            ->assertHasNoErrors(['form.topico', 'form.prioridad_id'])
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'prioridad_id' => $prioridad->id,
            'topico' => TopicoAtencion::Topico->value,
            'tiempo_espera_calculado' => 'Inmediato',
        ]);
    }

    public function test_panel_muestra_resumen_de_errores_al_guardar_datos_invalidos(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->set('form.fecha_hora_triaje', 'fecha invalida')
            ->call('guardar')
            ->assertHasErrors(['form.fecha_hora_triaje'])
            ->assertSee('No se pudo guardar la historia clinica')
            ->assertSee('fecha y hora de triaje');
    }

    public function test_panel_carga_signos_vitales_favoritos_en_triaje(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $favorito = SignoVital::query()->create([
            'descripcion' => 'Frecuencia cardiaca',
            'unidad' => 'lpm',
            'favorito' => true,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $noFavorito = SignoVital::query()->create([
            'descripcion' => 'Peso',
            'unidad' => 'kg',
            'favorito' => false,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->call('cargarSignosVitalesFavoritos')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('triaje_signos_vitales', [
            'signo_vital_id' => $favorito->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'triaje')
            ->assertSee('Cargar Signos Vitales')
            ->call('cargarSignosVitalesFavoritos')
            ->assertHasNoErrors()
            ->assertSee('Frecuencia cardiaca');

        $this->assertDatabaseHas('triaje_signos_vitales', [
            'hc_emergencia_id' => $atencion->id,
            'signo_vital_id' => $favorito->id,
            'valor' => '',
        ]);
        $this->assertDatabaseMissing('triaje_signos_vitales', [
            'hc_emergencia_id' => $atencion->id,
            'signo_vital_id' => $noFavorito->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->call('cargarSignosVitalesFavoritos')
            ->assertHasNoErrors();

        $this->assertSame(1, TriajeSignoVital::query()->where('hc_emergencia_id', $atencion->id)->count());
    }

    public function test_panel_triaje_gestiona_antecedentes_medicos_desde_drawer(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $alergia = Alergia::query()->create([
            'nombre' => 'Penicilina',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'triaje')
            ->assertSee('Antecedentes Médicos')
            ->assertSee('Alergias')
            ->assertSee('Reacción Adversa')
            ->assertSee('Antecedentes Familiares')
            ->assertSee('Cirugías')
            ->assertSee('Hospitalizaciones')
            ->assertSee('Medicamentos Habituales')
            ->assertSee('Antecedentes Patológicos')
            ->call('abrirDrawerAntecedente', 'alergias')
            ->set('antecedenteFormulario.alergia_id', (string) $alergia->id)
            ->set('antecedenteFormulario.agente_causal', 'Penicilina')
            ->set('antecedenteFormulario.periodo', 'Infancia')
            ->call('guardarAntecedenteRegistro')
            ->assertHasNoErrors()
            ->assertSee('Penicilina');

        $this->assertDatabaseHas('paciente_alergias', [
            'paciente_id' => $paciente->id,
            'alergia_id' => $alergia->id,
            'agente_causal' => 'Penicilina',
            'periodo' => 'Infancia',
        ]);
    }

    public function test_panel_triaje_permite_drawer_antecedentes_a_profesional_de_salud(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Lic. Rosa Perez']);
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Profesional de Salud',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'triaje')
            ->call('abrirDrawerAntecedente', 'medicamentos')
            ->assertSet('antecedenteSeccionActiva', 'medicamentos')
            ->set('antecedenteFormulario.medicacion', 'Losartan 50 mg')
            ->call('guardarAntecedenteRegistro')
            ->assertHasNoErrors()
            ->assertSee('Losartan 50 mg');

        $this->assertDatabaseHas('medicamentos_habituales', [
            'paciente_id' => $paciente->id,
            'medicacion' => 'Losartan 50 mg',
        ]);
    }

    public function test_panel_guarda_examenes_clinicos_y_calcula_glasgow(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        foreach ([
            ['tipo' => 'Ocular', 'puntaje' => 4, 'descripcion' => 'Apertura ocular espontanea'],
            ['tipo' => 'Verbal', 'puntaje' => 5, 'descripcion' => 'Respuesta verbal orientada'],
            ['tipo' => 'Motora', 'puntaje' => 6, 'descripcion' => 'Obedece ordenes'],
        ] as $registro) {
            Glasgow::query()->create($registro + [
                'registrado_por' => $medico->id,
                'actualizado_por' => $medico->id,
            ]);
        }

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'modo' => 'nuevo'])
            ->set('tabActiva', 'examenes')
            ->assertSee('Escala de Glasgow')
            ->assertSee('Evaluacion General')
            ->assertSee('Examen Regional')
            ->set('form.eg_ocular', 4)
            ->set('form.eg_verbal', 5)
            ->set('form.eg_motora', 6)
            ->set('form.estado_general', 'Paciente colaborador, no aparenta enfermedad aguda severa.')
            ->set('form.hidratacion', 'Adecuada. Mucosas humedas.')
            ->set('form.cabeza_cuello', 'Normocefalo. Pupilas isocoricas reactivas.')
            ->set('form.neurologico', 'Sin deficit motor ni sensitivo aparente.')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'eg_ocular' => 4,
            'eg_verbal' => 5,
            'eg_motora' => 6,
            'eg_total' => 15,
            'estado_general' => 'Paciente colaborador, no aparenta enfermedad aguda severa.',
            'hidratacion' => 'Adecuada. Mucosas humedas.',
            'cabeza_cuello' => 'Normocefalo. Pupilas isocoricas reactivas.',
            'neurologico' => 'Sin deficit motor ni sensitivo aparente.',
        ]);
    }

    public function test_panel_registra_diagnosticos_y_evoluciones_clinicas(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Castro']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnosticoIngreso = DiagnosticoCie10::query()->create([
            'codigo' => 'I10',
            'nombre' => 'Hipertension esencial primaria',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnosticoEgreso = DiagnosticoCie10::query()->create([
            'codigo' => 'E86.0',
            'nombre' => 'Deshidratacion leve',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'diagnosticos')
            ->assertSee('Diagnosticos de Ingreso')
            ->assertSee('Evoluciones Clinicas')
            ->assertSee('Diagnosticos de Egreso')
            ->call('abrirNuevoDiagnosticoIngreso')
            ->set('diagnosticoIngresoForm.diagnostico_cie10_id', $diagnosticoIngreso->id)
            ->set('diagnosticoIngresoForm.tipo_diagnostico', TipoDiagnostico::Presuntivo->value)
            ->set('diagnosticoIngresoForm.observacion', 'Controlar PA horariamente')
            ->call('guardarDiagnosticoIngreso')
            ->assertHasNoErrors()
            ->assertSee('Hipertension esencial primaria')
            ->call('abrirNuevaEvolucion')
            ->set('evolucionForm.fecha_hora', '2026-08-20T11:30')
            ->set('evolucionForm.profesional_salud_id', $profesional->id)
            ->set('evolucionForm.detalle_clinico', 'Paciente estable con medicacion antihipertensiva via oral.')
            ->call('guardarEvolucion')
            ->assertHasNoErrors()
            ->assertSee('Paciente estable con medicacion antihipertensiva via oral.')
            ->call('abrirNuevoDiagnosticoEgreso')
            ->set('diagnosticoEgresoForm.diagnostico_cie10_id', $diagnosticoEgreso->id)
            ->set('diagnosticoEgresoForm.clasificacion', ClasificacionDiagnosticoEgreso::Principal->value)
            ->set('diagnosticoEgresoForm.tipo_diagnostico', TipoDiagnostico::Definitivo->value)
            ->set('diagnosticoEgresoForm.observaciones', 'Alta con control ambulatorio')
            ->call('guardarDiagnosticoEgreso')
            ->assertHasNoErrors()
            ->assertSee('Deshidratacion leve');

        $this->assertDatabaseHas('ingreso_diagnosticos', [
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnosticoIngreso->id,
            'tipo_diagnostico' => TipoDiagnostico::Presuntivo->value,
            'observacion' => 'Controlar PA horariamente',
        ]);
        $this->assertDatabaseHas('evoluciones', [
            'hc_emergencia_id' => $atencion->id,
            'profesional_salud_id' => $profesional->id,
            'detalle_clinico' => 'Paciente estable con medicacion antihipertensiva via oral.',
        ]);
        $this->assertDatabaseHas('egreso_diagnosticos', [
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnosticoEgreso->id,
            'codigo_cie10' => 'E86.0',
            'denominacion' => 'Deshidratacion leve',
            'clasificacion' => ClasificacionDiagnosticoEgreso::Principal->value,
            'tipo_diagnostico' => TipoDiagnostico::Definitivo->value,
            'observaciones' => 'Alta con control ambulatorio',
        ]);
    }

    public function test_panel_registra_ordenes_y_examenes_de_laboratorio(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Carreno']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $tipoOrden = TipoOrden::query()->create([
            'descripcion' => 'Imagenologia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnostico = DiagnosticoCie10::query()->create([
            'codigo' => 'I10',
            'nombre' => 'Hipertension esencial',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnostico->id,
            'tipo_diagnostico' => TipoDiagnostico::Presuntivo,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $examen = Examen::query()->create([
            'descripcion' => 'Hemograma completo',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'ordenes')
            ->assertSee('Ordenes')
            ->assertSee('Genera Receta')
            ->assertSee(route('historia-clinica.emergencia.ordenes.pdf', $atencion), false)
            ->assertSee('Examenes de Laboratorio')
            ->assertSee(route('historia-clinica.emergencia.examenes.pdf', $atencion), false)
            ->call('abrirNuevaOrden')
            ->set('ordenForm.tipo_orden_id', $tipoOrden->id)
            ->set('ordenProcedimientoBusqueda', 'Rayos X de torax')
            ->set('ordenForm.prioridad', PrioridadOrden::Urgente->value)
            ->set('ordenForm.fecha_hora_solicitud', '2026-08-24T12:15')
            ->set('ordenForm.resultado', 'Pendiente')
            ->set('ordenForm.estado', EstadoSolicitudExamen::EnProceso->value)
            ->set('ordenProfesionalBusqueda', 'Carreno')
            ->call('seleccionarProfesionalOrden', $profesional->id)
            ->call('guardarOrden')
            ->assertHasNoErrors()
            ->assertSee('Rayos X de torax')
            ->call('abrirNuevaSolicitudExamen')
            ->set('solicitudExamenForm.examen_id', $examen->id)
            ->set('solicitudExamenForm.prioridad', PrioridadSolicitudExamen::Emergencia->value)
            ->set('solicitudExamenForm.fecha_hora_solicitud', '2026-08-24T10:30')
            ->set('solicitudExamenForm.resultado', 'Ver resultados')
            ->set('solicitudExamenForm.estado', EstadoSolicitudExamen::Terminado->value)
            ->set('solicitudExamenProfesionalBusqueda', 'Carreno')
            ->call('seleccionarProfesionalSolicitudExamen', $profesional->id)
            ->call('guardarSolicitudExamen')
            ->assertHasNoErrors()
            ->assertSee('Hemograma completo');

        $this->assertDatabaseHas('ordenes', [
            'hc_emergencia_id' => $atencion->id,
            'tipo_orden_id' => $tipoOrden->id,
            'detalle' => 'Rayos X de torax',
            'prioridad' => PrioridadOrden::Urgente->value,
            'resultado' => 'Pendiente',
            'estado' => EstadoSolicitudExamen::EnProceso->value,
            'profesional_salud_id' => $profesional->id,
        ]);
        $this->assertDatabaseHas('procedimientos', [
            'denominacion' => 'Rayos X de torax',
            'estado' => 'Activo',
        ]);
        $this->assertDatabaseHas('solicitud_examenes', [
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'prioridad' => PrioridadSolicitudExamen::Emergencia->value,
            'resultado' => 'Ver resultados',
            'estado' => EstadoSolicitudExamen::Terminado->value,
            'profesional_salud_id' => $profesional->id,
        ]);

        $this->actingAs($medico)
            ->get(route('historia-clinica.emergencia.ordenes.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($medico)
            ->get(route('historia-clinica.emergencia.examenes.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_panel_carga_lista_y_elimina_documentos_adjuntos_de_orden(): void
    {
        Storage::fake('public');

        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Roel Carreno Cisneros']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $tipoOrden = TipoOrden::query()->create([
            'descripcion' => 'Imagenologia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $orden = Orden::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'tipo_orden_id' => $tipoOrden->id,
            'detalle' => 'Radiografia de torax',
            'prioridad' => PrioridadOrden::Urgente,
            'estado' => EstadoSolicitudExamen::Solicitado,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        DocumentoAdjunto::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'tipo' => TipoDocumentoAdjunto::Pdf,
            'contenido' => 'documento-general.pdf',
            'ruta_archivo' => 'historias-clinicas/'.$atencion->id.'/documentos/documento-general.pdf',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        $archivo = UploadedFile::fake()->create('resultado-orden.pdf', 180, 'application/pdf');

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'ordenes')
            ->assertSee('Cargar archivos de la orden')
            ->call('abrirModalDocumentosOrden', $orden->id)
            ->assertSet('modalDocumentosOrdenAbierto', true)
            ->assertDontSee('documento-general.pdf')
            ->set('ordenDocumentosArchivos', [$archivo])
            ->call('guardarDocumentosOrden')
            ->assertHasNoErrors()
            ->assertSee('resultado-orden.pdf');

        $documento = DocumentoAdjunto::query()
            ->where('orden_id', $orden->id)
            ->firstOrFail();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id' => $documento->id,
            'hc_emergencia_id' => $atencion->id,
            'orden_id' => $orden->id,
            'contenido' => 'resultado-orden.pdf',
            'tipo' => TipoDocumentoAdjunto::Pdf->value,
        ]);
        Storage::disk('public')->assertExists($documento->ruta_archivo);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->call('abrirModalDocumentosOrden', $orden->id)
            ->call('eliminarDocumentoOrden', $documento->id)
            ->assertHasNoErrors()
            ->assertDontSee('resultado-orden.pdf');

        $this->assertDatabaseMissing('documentos_adjuntos', [
            'id' => $documento->id,
        ]);
        Storage::disk('public')->assertMissing($documento->ruta_archivo);
    }

    public function test_panel_registra_prescripciones_y_procedimientos_realizados(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Carreno']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnostico = DiagnosticoCie10::query()->create([
            'codigo' => 'J18',
            'nombre' => 'Neumonia no especificada',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnostico->id,
            'tipo_diagnostico' => TipoDiagnostico::Presuntivo,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $medicamento = Medicamento::query()->create([
            'descripcion' => 'Ceftriaxona',
            'presentacion' => 'Vial 2 g',
            'generico' => 'Ceftriaxona',
            'stock' => 20,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        Procedimiento::query()->create([
            'codigo' => '96365',
            'denominacion' => 'Infusion intravenosa de medicamento',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'medicamentos')
            ->assertSee('Prescripción de Medicamentos')
            ->assertSee('Genera Receta')
            ->assertSee(route('historia-clinica.emergencia.medicamentos.pdf', $atencion), false)
            ->assertSee('Procedimientos Realizados')
            ->call('abrirNuevaPrescripcion')
            ->set('prescripcionMedicamentoBusqueda', 'Ceftriaxona')
            ->call('seleccionarMedicamentoPrescripcion', $medicamento->id)
            ->set('prescripcionForm.dosis', '1 g c/12 h')
            ->set('prescripcionForm.via', 'EV')
            ->set('prescripcionForm.duracion', '7 dias')
            ->set('prescripcionForm.cantidad', '2')
            ->set('prescripcionForm.fecha_hora', '2026-08-24T15:00')
            ->set('prescripcionForm.estado', EstadoSolicitudExamen::Terminado->value)
            ->set('prescripcionProfesionalBusqueda', 'Carreno')
            ->call('seleccionarProfesionalPrescripcion', $profesional->id)
            ->call('guardarPrescripcion')
            ->assertHasNoErrors()
            ->assertSee('Ceftriaxona')
            ->call('abrirNuevoProcedimiento')
            ->set('procedimientoBusqueda', 'Sutura simple de herida')
            ->set('procedimientoForm.sesiones', '1')
            ->set('procedimientoForm.fecha_hora', '2026-08-24T15:30')
            ->set('procedimientoForm.estado', EstadoSolicitudExamen::Terminado->value)
            ->call('guardarProcedimiento')
            ->assertHasNoErrors()
            ->assertSee('Sutura simple de herida');

        $this->assertDatabaseHas('prescripciones', [
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $medicamento->id,
            'medicamento' => 'Ceftriaxona',
            'presentacion' => 'Vial 2 g',
            'dosis' => '1 g c/12 h',
            'via' => 'EV',
            'duracion' => '7 dias',
            'cantidad' => 2,
            'estado' => EstadoSolicitudExamen::Terminado->value,
            'profesional_salud_id' => $profesional->id,
        ]);
        $this->assertDatabaseHas('procedimientos_realizados', [
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => null,
            'denominacion' => 'Sutura simple de herida',
            'sesiones' => 1,
            'estado' => EstadoSolicitudExamen::Terminado->value,
        ]);

        $this->actingAs($medico)
            ->get(route('historia-clinica.emergencia.medicamentos.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_panel_registra_notas_de_enfermeria_y_balance_hidrico(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Lic. Martha Rojas']);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'enfermeria')
            ->assertSee('Notas de Enfermería')
            ->assertSee('Balance Hídrico')
            ->call('abrirNuevaNotaEnfermeria')
            ->set('notaEnfermeriaForm.fecha_hora', '2026-08-24T08:00')
            ->set('profesionalEnfermeriaBusqueda', 'Martha')
            ->call('seleccionarProfesionalEnfermeria', $profesional->id)
            ->set('notaEnfermeriaForm.nota', 'Paciente despierta, orientada. Diuresis espontanea positiva.')
            ->set('notaEnfermeriaForm.firma', 'Lic. Martha')
            ->call('guardarNotaEnfermeria')
            ->assertHasNoErrors()
            ->assertSee('Paciente despierta')
            ->set('form.ingresos', '1500 ml')
            ->set('form.egresos', '1200 ml')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('enfermeria_notas', [
            'hc_emergencia_id' => $atencion->id,
            'profesional_salud_id' => $profesional->id,
            'nota' => 'Paciente despierta, orientada. Diuresis espontanea positiva.',
            'firma' => 'Lic. Martha',
        ]);
        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'ingresos' => '1500 ml',
            'egresos' => '1200 ml',
            'balance' => '300 ml',
        ]);
    }

    public function test_panel_registra_interconsultas_con_profesional_autocompletado(): void
    {
        $especialidad = Especialidad::query()->create(['nombre' => 'Cardiologia']);
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Dr. Velasquez',
            'especialidad_id' => $especialidad->id,
            'cmp' => '456789',
        ]);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'interconsultas')
            ->assertSee('Registro de interconsultas')
            ->call('abrirNuevaInterconsulta')
            ->set('interconsultaForm.fecha_hora', '2026-08-24T10:45')
            ->set('profesionalInterconsultaBusqueda', 'Velasquez')
            ->call('seleccionarProfesionalInterconsulta', $profesional->id)
            ->set('interconsultaForm.nota', 'Se sugiere monitoreo continuo de PA y ajuste de medicacion antihipertensiva.')
            ->call('guardarInterconsulta')
            ->assertHasNoErrors()
            ->assertSee('Dr. Velasquez')
            ->assertSee('Cardiologia')
            ->assertSee('Se sugiere monitoreo continuo');

        $this->assertDatabaseHas('interconsultas', [
            'hc_emergencia_id' => $atencion->id,
            'profesional_salud_id' => $profesional->id,
            'nota' => 'Se sugiere monitoreo continuo de PA y ajuste de medicacion antihipertensiva.',
        ]);
    }

    public function test_panel_registra_movimientos_transferencias_y_referencias(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Dr. Castro',
            'cmp' => '123456',
        ]);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $upsOrigen = Ups::factory()->create([
            'codigo' => 'EMG',
            'denominacion' => 'Emergencia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $upsDestino = Ups::factory()->create([
            'codigo' => 'UCI',
            'denominacion' => 'Unidad de Cuidados Intensivos',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'movimientos')
            ->assertSee('Trauma Shock')
            ->assertSee('Transferencias y Movimientos Internos')
            ->assertSee('Referencias a otros Establecimientos')
            ->set('form.en_shock_trauma', 'Si')
            ->set('form.shock_trauma_fecha_hora', '2026-08-24T10:15')
            ->set('shockTraumaResponsableBusqueda', 'Castro')
            ->call('seleccionarResponsableShockTrauma', $profesional->id)
            ->set('form.shock_trauma_resultado', 'Traslado critico')
            ->set('form.shock_trauma_destino', 'Unidad de Cuidados Intensivos (UCI)')
            ->set('form.registro_especializado', 'Paciente con inestabilidad hemodinamica post-reanimacion.')
            ->set('referenciaForm.ipress_origen', 'Hospital Regional HCEApp')
            ->set('upsOrigenBusqueda', 'Emergencia')
            ->call('seleccionarUpsOrigen', $upsOrigen->id)
            ->set('referenciaForm.ipress_referencia', 'Hospital Nacional de Referencia')
            ->set('upsDestinoBusqueda', 'Cuidados Intensivos')
            ->call('seleccionarUpsDestino', $upsDestino->id)
            ->set('referenciaForm.fecha_hora_solicitud', '2026-08-24T12:00')
            ->set('referenciaForm.respuesta_solicitud', 'Pendiente')
            ->set('referenciaForm.motivo_referencia', 'Traslado para manejo especializado')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?')
            ->call('seguirEditandoHistoria')
            ->assertSet('tabActiva', 'movimientos')
            ->assertSet('panelConfirmacionGuardadoAbierto', false)
            ->call('abrirNuevaTransferencia')
            ->set('transferenciaForm.fecha_hora', '2026-08-24T11:45')
            ->set('transferenciaForm.origen', 'Emergencia')
            ->set('transferenciaForm.destino', 'Unidad de Cuidados Intensivos')
            ->set('transferenciaForm.motivo', 'Inestabilidad hemodinamica post-reanimacion')
            ->set('transferenciaResponsableBusqueda', 'Responsable externo')
            ->call('guardarTransferencia')
            ->assertHasNoErrors()
            ->assertSee('Inestabilidad hemodinamica');

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'en_shock_trauma' => 'Si',
            'shock_trauma_resultado' => 'Traslado critico',
            'shock_trauma_destino' => 'Unidad de Cuidados Intensivos (UCI)',
            'registro_especializado' => 'Paciente con inestabilidad hemodinamica post-reanimacion.',
        ]);
        $this->assertDatabaseHas('transferencias', [
            'hc_emergencia_id' => $atencion->id,
            'origen' => 'Emergencia',
            'destino' => 'Unidad de Cuidados Intensivos',
            'motivo' => 'Inestabilidad hemodinamica post-reanimacion',
            'responsable' => 'Responsable externo',
        ]);
        $this->assertDatabaseHas('hc_referencias', [
            'hc_emergencia_id' => $atencion->id,
            'ipress_origen' => 'Hospital Regional HCEApp',
            'ups_origen_id' => $upsOrigen->id,
            'ipress_referencia' => 'Hospital Nacional de Referencia',
            'ups_destino_id' => $upsDestino->id,
            'motivo_referencia' => 'Traslado para manejo especializado',
        ]);
    }

    public function test_panel_registra_egreso_y_medicamentos_del_alta(): void
    {
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Dr. Roel Carreno Cisneros',
            'cmp' => '1234',
        ]);
        $medico = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $medicamento = Medicamento::query()->create([
            'descripcion' => 'Paracetamol',
            'presentacion' => 'Tableta 500mg',
            'generico' => 'Paracetamol',
            'stock' => 60,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnosticoIngreso = DiagnosticoCie10::query()->create([
            'codigo' => 'S01',
            'nombre' => 'Herida de cabeza',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        $diagnosticoEgreso = DiagnosticoCie10::query()->create([
            'codigo' => 'Z54',
            'nombre' => 'Convalecencia',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        IngresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnosticoIngreso->id,
            'tipo_diagnostico' => TipoDiagnostico::Presuntivo,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);
        EgresoDiagnostico::query()->create([
            'hc_emergencia_id' => $atencion->id,
            'diagnostico_cie10_id' => $diagnosticoEgreso->id,
            'codigo_cie10' => 'Z54',
            'denominacion' => 'Convalecencia',
            'clasificacion' => ClasificacionDiagnosticoEgreso::Principal,
            'tipo_diagnostico' => TipoDiagnostico::Definitivo,
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'egresos')
            ->assertSee('Destino del Paciente y condición')
            ->assertSee('Medicamentos del Alta')
            ->assertSee('Datos del Egreso')
            ->assertSee('Genera Ficha de Alta')
            ->assertSee(route('historia-clinica.emergencia.alta.pdf', $atencion), false)
            ->call('abrirNuevoAltaMedicamento')
            ->set('altaMedicamentoBusqueda', 'Paracetamol')
            ->call('seleccionarMedicamentoAlta', $medicamento->id)
            ->set('altaMedicamentoForm.dosis', '1 tableta')
            ->set('altaMedicamentoForm.via', 'Oral')
            ->set('altaMedicamentoForm.cantidad', '10')
            ->set('altaMedicamentoForm.indicaciones', 'Tomar cada 8 horas por 3 dias si hay dolor.')
            ->call('guardarAltaMedicamento')
            ->assertHasNoErrors()
            ->assertSee('Paracetamol')
            ->set('form.destino_dentro_ipress', DestinoDentroIpress::Hospitalizacion->value)
            ->set('form.destino_fuera_ipress', DestinoFueraIpress::No->value)
            ->set('form.condicion_egreso', CondicionEgreso::Mejorado->value)
            ->set('form.fecha_hora_egreso', '2026-08-24T15:30')
            ->set('profesionalEgresoBusqueda', 'Carreno')
            ->call('seleccionarProfesionalEgreso', $profesional->id)
            ->set('form.indicaciones_finales', 'Control por emergencia si presenta signos de alarma.')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('alta_medicamentos', [
            'hc_emergencia_id' => $atencion->id,
            'medicamento_id' => $medicamento->id,
            'medicamento' => 'Paracetamol',
            'presentacion' => 'Tableta 500mg',
            'dosis' => '1 tableta',
            'via' => 'Oral',
            'cantidad' => 10,
            'indicaciones' => 'Tomar cada 8 horas por 3 dias si hay dolor.',
        ]);
        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'destino_dentro_ipress' => DestinoDentroIpress::Hospitalizacion->value,
            'destino_fuera_ipress' => DestinoFueraIpress::No->value,
            'condicion_egreso' => CondicionEgreso::Mejorado->value,
            'profesional_egreso_id' => $profesional->id,
            'indicaciones_finales' => 'Control por emergencia si presenta signos de alarma.',
        ]);

        $this->actingAs($medico)
            ->get(route('historia-clinica.emergencia.alta.pdf', $atencion))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_panel_carga_lista_y_elimina_documentos_adjuntos(): void
    {
        Storage::fake('public');

        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Supervisor',
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $archivo = UploadedFile::fake()->create('Radiografia_Torax.pdf', 2048, 'application/pdf');

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'documentacion')
            ->assertSee('Documentación Adjunta')
            ->set('documentosAdjuntosArchivos', [$archivo])
            ->call('guardarDocumentosAdjuntos')
            ->assertHasNoErrors()
            ->assertSee('Radiografia_Torax.pdf');

        $documento = DocumentoAdjunto::query()->firstOrFail();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id' => $documento->id,
            'hc_emergencia_id' => $atencion->id,
            'tipo' => TipoDocumentoAdjunto::Pdf->value,
            'contenido' => 'Radiografia_Torax.pdf',
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        Storage::disk('public')->assertExists($documento->ruta_archivo);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'documentacion')
            ->call('eliminarDocumentoAdjunto', $documento->id)
            ->assertHasNoErrors()
            ->assertDontSee('Radiografia_Torax.pdf');

        $this->assertDatabaseMissing('documentos_adjuntos', [
            'id' => $documento->id,
        ]);
        Storage::disk('public')->assertMissing($documento->ruta_archivo);
    }

    public function test_edicion_de_historia_clinica_solo_es_accesible_para_tipos_autorizados(): void
    {
        $usuarioAdministrativo = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Administrativo',
        ]);
        $usuarioSupervisor = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Supervisor',
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuarioAdministrativo->id,
            'actualizado_por' => $usuarioAdministrativo->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuarioAdministrativo->id,
            'actualizado_por' => $usuarioAdministrativo->id,
        ]);

        Livewire::actingAs($usuarioAdministrativo)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('verAtencion', $atencion->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.ver', [$paciente, $atencion]));

        Livewire::actingAs($usuarioAdministrativo)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('editarAtencion', $atencion->id)
            ->assertForbidden();

        Livewire::actingAs($usuarioAdministrativo)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->assertDontSee('wire:click="editarAtencion('.$atencion->id.')"', false);

        Livewire::actingAs($usuarioAdministrativo)
            ->test(HistoriasAbiertasPanel::class)
            ->assertDontSee('wire:click="editarAtencion('.$atencion->id.')"', false)
            ->call('editarAtencion', $atencion->id)
            ->assertForbidden();

        $this->actingAs($usuarioAdministrativo)
            ->get(route('historia-clinica.atenciones-paciente.emergencia.editar', [$paciente, $atencion]))
            ->assertForbidden();

        Livewire::actingAs($usuarioSupervisor)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('editarAtencion', $atencion->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.editar', [$paciente, $atencion]));

        Livewire::actingAs($usuarioSupervisor)
            ->test(HistoriasAbiertasPanel::class)
            ->assertSee('wire:click="editarAtencion('.$atencion->id.')"', false)
            ->call('editarAtencion', $atencion->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.editar', [$paciente, $atencion]));

        Livewire::actingAs($usuarioSupervisor)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSee('Atencion de emergencia')
            ->assertSee('AT:')
            ->assertSee('--');
    }

    public function test_panel_historia_clinica_advierte_cambios_sin_guardar_antes_de_salir(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Guardia Emergencia']);
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'alias' => 'Alias original',
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSet('hayCambiosSinGuardar', false)
            ->set('form.alias', 'Alias modificado')
            ->assertSet('hayCambiosSinGuardar', true)
            ->call('solicitarSalidaDelPanel')
            ->assertSet('panelSalidaSinGuardarAbierto', true)
            ->assertSee('¿Desea guardar los cambios antes de salir?')
            ->call('cancelarSalidaDelPanel')
            ->assertSet('panelSalidaSinGuardarAbierto', false)
            ->call('solicitarSalidaDelPanel')
            ->call('salirSinGuardarCambios')
            ->assertRedirect(route('historia-clinica.atenciones-paciente.historia', $paciente));

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'alias' => 'Alias original',
        ]);
    }

    public function test_panel_historia_clinica_guarda_cambios_antes_de_salir(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Guardia Emergencia']);
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'alias' => 'Alias original',
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('form.alias', 'Alias guardado antes de salir')
            ->call('solicitarSalidaDelPanel')
            ->assertSet('panelSalidaSinGuardarAbierto', true)
            ->call('guardarYSalirDelPanel')
            ->assertRedirect(route('historia-clinica.atenciones-paciente.historia', $paciente));

        $this->assertDatabaseHas('hc_emergencia', [
            'id' => $atencion->id,
            'alias' => 'Alias guardado antes de salir',
        ]);
    }

    public function test_panel_historia_clinica_advierte_cambios_detectados_en_cliente_antes_de_sincronizar(): void
    {
        $profesional = ProfesionalSalud::factory()->create(['nombres' => 'Dr. Guardia Emergencia']);
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
        $paciente = Paciente::factory()->create([
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);
        $atencion = HcEmergencia::factory()->create([
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'estado_atencion' => EstadoAtencion::Abierta,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSet('hayCambiosSinGuardar', false)
            ->call('solicitarSalidaAUrl', route('historia-clinica.atenciones-paciente.index'), true)
            ->assertSet('hayCambiosSinGuardar', true)
            ->assertSet('panelSalidaSinGuardarAbierto', true)
            ->assertSee('¿Desea guardar los cambios antes de salir?');
    }

    private function crearUsuarioConPermiso(string $permiso, array $atributos = []): User
    {
        $this->seed(RolesPermisosSeeder::class);

        $usuario = User::factory()->create(array_merge(['activo' => true, 'tipo_usuario' => 'Médico'], $atributos));
        $usuario->givePermissionTo($permiso);

        return $usuario;
    }
}
