<?php

namespace Tests\Feature;

use App\Enums\EstadoCatalogo;
use App\Livewire\GestionClinica\RegistroClinicoPanel;
use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\ProfesionalSalud;
use App\Models\User;
use App\Services\GestionClinica\RegistrosClinicosArchivoService;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class GestionClinicaRegistrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_con_permiso_visualiza_pacientes_y_profesionales(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::PACIENTES_VER);
        $usuario->givePermissionTo(Permisos::PROFESIONALES_VER);

        $this->actingAs($usuario)
            ->get(route('gestion-clinica.pacientes.index'))
            ->assertOk()
            ->assertSee('Pacientes')
            ->assertSee('Total registros')
            ->assertSee('Acciones');

        $this->actingAs($usuario)
            ->get(route('gestion-clinica.profesionales.index'))
            ->assertOk()
            ->assertSee('Profesionales')
            ->assertSee('Total registros')
            ->assertSee('Estado')
            ->assertSee('Acciones');
    }

    public function test_administrador_gestiona_paciente_desde_panel(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(RegistroClinicoPanel::class, ['registroClinico' => 'pacientes'])
            ->call('abrirNuevo')
            ->set('form.numero_historia', 'HC-001')
            ->set('form.nombre', 'Paciente de prueba')
            ->set('form.direccion', 'Direccion de prueba')
            ->set('form.telefono', '999888777')
            ->set('form.sexo', 'Masculino')
            ->set('form.fecha_nacimiento', '1990-01-15')
            ->set('form.tipo_documento', 'DNI')
            ->set('form.numero_documento', '70000001')
            ->set('form.grupo_sanguineo', 'O')
            ->set('form.factor_rh', '+')
            ->set('form.id_paciente_externo', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $paciente = Paciente::query()->where('numero_documento', '70000001')->firstOrFail();

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'numero_historia' => 'HC-001',
            'nombre' => 'Paciente de prueba',
            'telefono' => '999888777',
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(RegistroClinicoPanel::class, ['registroClinico' => 'pacientes'])
            ->call('editar', $paciente->id)
            ->set('form.direccion', 'Direccion actualizada')
            ->set('form.telefono', '999111222')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pacientes', [
            'id' => $paciente->id,
            'direccion' => 'Direccion actualizada',
            'telefono' => '999111222',
        ]);
    }

    public function test_paginador_de_pacientes_usa_url_absoluta_correcta(): void
    {
        $administrador = $this->crearAdministrador();

        foreach (range(1, 6) as $numero) {
            Paciente::factory()->create([
                'nombre' => 'Paciente paginado '.$numero,
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ]);
        }

        Livewire::actingAs($administrador)
            ->test(RegistroClinicoPanel::class, ['registroClinico' => 'pacientes'])
            ->set('busqueda', 'Paciente')
            ->assertSee(route('gestion-clinica.pacientes.index'), false)
            ->assertSee('busqueda=Paciente', false)
            ->assertSee('page=2', false)
            ->assertDontSee('/gestion-clinica/gestion-clinica/pacientes', false);
    }

    public function test_administrador_gestiona_profesional_y_cambia_estado(): void
    {
        $administrador = $this->crearAdministrador();
        $especialidad = Especialidad::query()->create([
            'nombre' => 'Emergencia',
            'descripcion' => 'Atencion medica de emergencia',
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(RegistroClinicoPanel::class, ['registroClinico' => 'profesionales'])
            ->call('abrirNuevo')
            ->set('form.nombres', 'Profesional de prueba')
            ->set('form.especialidad_id', $especialidad->id)
            ->set('form.tipo_colegiatura', 'CMP')
            ->set('form.cmp', '123456')
            ->set('form.rne', '654321')
            ->set('form.codigo_servicio', 'EMG')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $profesional = ProfesionalSalud::query()->where('cmp', '123456')->firstOrFail();

        $this->assertDatabaseHas('profesionales_salud', [
            'id' => $profesional->id,
            'especialidad_id' => $especialidad->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(RegistroClinicoPanel::class, ['registroClinico' => 'profesionales'])
            ->call('cambiarEstado', $profesional->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profesionales_salud', [
            'id' => $profesional->id,
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);
    }

    public function test_herramientas_masivas_funcionan_para_pacientes_y_profesionales(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::PACIENTES_VER);
        $administrador = $this->crearAdministrador();

        Paciente::factory()->create([
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
        ProfesionalSalud::factory()->create([
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('gestion-clinica.pacientes.exportar.xlsx'))
            ->assertForbidden();

        foreach (['pacientes', 'profesionales'] as $registroClinico) {
            $this->actingAs($administrador)
                ->get(route('gestion-clinica.'.$registroClinico.'.exportar.xlsx'))
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $this->actingAs($administrador)
                ->get(route('gestion-clinica.'.$registroClinico.'.plantilla'))
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            $this->actingAs($administrador)
                ->get(route('gestion-clinica.'.$registroClinico.'.exportar.pdf'))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    public function test_servicio_importa_pacientes_desde_plantilla_excel(): void
    {
        $administrador = $this->crearAdministrador();
        $servicio = app(RegistrosClinicosArchivoService::class);
        $rutaPlantilla = $servicio->generarPlantilla('pacientes');
        $archivo = new UploadedFile(
            $rutaPlantilla,
            'plantilla_pacientes.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = $servicio->importar('pacientes', $archivo, $administrador->id);

        @unlink($rutaPlantilla);

        $this->assertSame(2, $resultado['creados']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('pacientes', [
            'numero_historia' => 'HC-2026-0001',
            'nombre' => 'Paciente ejemplo 1',
            'telefono' => '999000001',
            'numero_documento' => '70000001',
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
    }

    private function crearAdministrador(): User
    {
        $this->seed(RolesPermisosSeeder::class);

        $administrador = User::factory()->create(['activo' => true]);
        $administrador->assignRole('Administrador');

        return $administrador;
    }

    private function crearUsuarioConPermiso(string $permiso): User
    {
        $this->seed(RolesPermisosSeeder::class);

        $usuario = User::factory()->create(['activo' => true]);
        $usuario->givePermissionTo($permiso);

        return $usuario;
    }
}
