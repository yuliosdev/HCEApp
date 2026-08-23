<?php

namespace Tests\Feature;

use App\Livewire\HistoriaClinica\AtencionesPacientePanel;
use App\Livewire\HistoriaClinica\HcEmergenciaPanel;
use App\Livewire\HistoriaClinica\HistoriaClinicaPaciente;
use App\Livewire\HistoriaClinica\PacienteDashboard;
use App\Enums\ColorTriaje;
use App\Enums\EstadoAtencion;
use App\Enums\GravedadReaccionAdversaMedicamento;
use App\Enums\TipoDiagnostico;
use App\Enums\TipoReaccionAdversaMedicamento;
use App\Models\Alergia;
use App\Models\AntecedentePatologico;
use App\Models\DiagnosticoCie10;
use App\Models\HcEmergencia;
use App\Models\IngresoDiagnostico;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\Prioridad;
use App\Models\ProfesionalSalud;
use App\Models\ReaccionAdversaMedicamento;
use App\Models\SignoVital;
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
            ->assertRedirect();

        $this->assertDatabaseHas('hc_emergencia', [
            'paciente_id' => $paciente->id,
            'profesional_salud_id' => $profesional->id,
            'prioridad_id' => $prioridad->id,
            'estado_atencion' => EstadoAtencion::Abierta->value,
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
