<?php

namespace Tests\Feature;

use App\Livewire\HistoriaClinica\AtencionesPacientePanel;
use App\Livewire\HistoriaClinica\HcEmergenciaPanel;
use App\Livewire\HistoriaClinica\HistoriaClinicaPaciente;
use App\Livewire\HistoriaClinica\PacienteDashboard;
use App\Enums\ColorTriaje;
use App\Enums\ClasificacionDiagnosticoEgreso;
use App\Enums\EstadoAtencion;
use App\Enums\EstadoSolicitudExamen;
use App\Enums\GravedadReaccionAdversaMedicamento;
use App\Enums\PrioridadOrden;
use App\Enums\PrioridadSolicitudExamen;
use App\Enums\TipoDiagnostico;
use App\Enums\TipoReaccionAdversaMedicamento;
use App\Models\Alergia;
use App\Models\AntecedentePatologico;
use App\Models\DiagnosticoCie10;
use App\Models\Especialidad;
use App\Models\Examen;
use App\Models\Glasgow;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Medicamento;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\Prioridad;
use App\Models\Procedimiento;
use App\Models\ProfesionalSalud;
use App\Models\ReaccionAdversaMedicamento;
use App\Models\SignoVital;
use App\Models\TipoOrden;
use App\Models\TriajeSignoVital;
use App\Models\User;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('157795')
            ->assertSee('155248')
            ->assertSee('Dra. Carmen Mendoza')
            ->assertSee('Hipertension esencial (I10)')
            ->assertSee('Nivel 2')
            ->assertSee('Abierta')
            ->assertSee('Cerrada')
            ->assertSee('Editar atencion');
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
            ->assertSee('Triaje / Anamnesis');
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
            ->assertSee('Triaje / Anamnesis')
            ->set('form.prioridad_id', $prioridad->id)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('¿Deseas seguir editando la Historia Clínica?');

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'estado_atencion' => EstadoAtencion::Abierta->value,
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
            'topico' => 'Topico',
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
            'topico' => 'Topico',
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
        $examen = Examen::query()->create([
            'descripcion' => 'Hemograma completo',
            'registrado_por' => $medico->id,
            'actualizado_por' => $medico->id,
        ]);

        Livewire::actingAs($medico)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->set('tabActiva', 'ordenes')
            ->assertSee('Ordenes')
            ->assertSee('Examenes de Laboratorio')
            ->call('abrirNuevaOrden')
            ->set('ordenForm.tipo_orden_id', $tipoOrden->id)
            ->set('ordenForm.detalle', 'Rayos X de torax')
            ->set('ordenForm.prioridad', PrioridadOrden::Urgente->value)
            ->set('ordenForm.fecha_hora_solicitud', '2026-08-24T12:15')
            ->set('ordenForm.resultado', 'Pendiente')
            ->set('ordenForm.profesional_salud_id', $profesional->id)
            ->call('guardarOrden')
            ->assertHasNoErrors()
            ->assertSee('Rayos X de torax')
            ->call('abrirNuevaSolicitudExamen')
            ->set('solicitudExamenForm.examen_id', $examen->id)
            ->set('solicitudExamenForm.prioridad', PrioridadSolicitudExamen::Emergencia->value)
            ->set('solicitudExamenForm.fecha_hora_solicitud', '2026-08-24T10:30')
            ->set('solicitudExamenForm.resultado', 'Ver resultados')
            ->set('solicitudExamenForm.estado', EstadoSolicitudExamen::Terminado->value)
            ->set('solicitudExamenForm.profesional_salud_id', $profesional->id)
            ->call('guardarSolicitudExamen')
            ->assertHasNoErrors()
            ->assertSee('Hemograma completo');

        $this->assertDatabaseHas('ordenes', [
            'hc_emergencia_id' => $atencion->id,
            'tipo_orden_id' => $tipoOrden->id,
            'detalle' => 'Rayos X de torax',
            'prioridad' => PrioridadOrden::Urgente->value,
            'resultado' => 'Pendiente',
            'profesional_salud_id' => $profesional->id,
        ]);
        $this->assertDatabaseHas('solicitud_examenes', [
            'hc_emergencia_id' => $atencion->id,
            'examen_id' => $examen->id,
            'prioridad' => PrioridadSolicitudExamen::Emergencia->value,
            'resultado' => 'Ver resultados',
            'estado' => EstadoSolicitudExamen::Terminado->value,
            'profesional_salud_id' => $profesional->id,
        ]);
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
            ->assertSee('Procedimientos Realizados')
            ->call('abrirNuevaPrescripcion')
            ->set('prescripcionMedicamentoBusqueda', 'Ceftriaxona')
            ->call('seleccionarMedicamentoPrescripcion', $medicamento->id)
            ->set('prescripcionForm.dosis', '1 g c/12 h')
            ->set('prescripcionForm.via', 'EV')
            ->set('prescripcionForm.duracion', '7 dias')
            ->set('prescripcionForm.cantidad', '2')
            ->set('prescripcionForm.fecha_hora', '2026-08-24T15:00')
            ->set('prescripcionForm.profesional_salud_id', $profesional->id)
            ->call('guardarPrescripcion')
            ->assertHasNoErrors()
            ->assertSee('Ceftriaxona')
            ->call('abrirNuevoProcedimiento')
            ->set('procedimientoBusqueda', 'Sutura simple de herida')
            ->set('procedimientoForm.sesiones', '1')
            ->set('procedimientoForm.fecha_hora', '2026-08-24T15:30')
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
            'profesional_salud_id' => $profesional->id,
        ]);
        $this->assertDatabaseHas('procedimientos_realizados', [
            'hc_emergencia_id' => $atencion->id,
            'procedimiento_id' => null,
            'denominacion' => 'Sutura simple de herida',
            'sesiones' => 1,
        ]);
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

    public function test_panel_ver_y_editar_atencion_es_accesible_para_cualquier_tipo_de_usuario(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::HISTORIA_CLINICA_VER, [
            'tipo_usuario' => 'Administrativo',
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

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('verAtencion', $atencion->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.ver', [$paciente, $atencion]));

        Livewire::actingAs($usuario)
            ->test(HistoriaClinicaPaciente::class, ['paciente' => $paciente])
            ->call('editarAtencion', $atencion->id)
            ->assertRedirect(route('historia-clinica.atenciones-paciente.emergencia.editar', [$paciente, $atencion]));

        Livewire::actingAs($usuario)
            ->test(HcEmergenciaPanel::class, ['paciente' => $paciente, 'atencion' => $atencion, 'modo' => 'editar'])
            ->assertSee('Atencion de emergencia')
            ->assertSee((string) $atencion->numero_atencion);
    }

    private function crearUsuarioConPermiso(string $permiso, array $atributos = []): User
    {
        $this->seed(RolesPermisosSeeder::class);

        $usuario = User::factory()->create(array_merge(['activo' => true], $atributos));
        $usuario->givePermissionTo($permiso);

        return $usuario;
    }
}
