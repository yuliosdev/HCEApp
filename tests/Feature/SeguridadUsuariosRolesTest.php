<?php

namespace Tests\Feature;

use App\Livewire\Seguridad\RolesPermisosPanel;
use App\Livewire\Seguridad\UsuariosPanel;
use App\Models\ProfesionalSalud;
use App\Models\User;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadUsuariosRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_seeder_crea_roles_y_permisos_base(): void
    {
        $this->seed(RolesPermisosSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Administrador', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Medico', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => Permisos::USUARIOS_VER, 'guard_name' => 'web']);
        $this->assertTrue(Role::findByName('Administrador')->hasPermissionTo(Permisos::ROLES_EDITAR));
    }

    public function test_usuario_sin_permiso_no_puede_entrar_a_seguridad(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('seguridad.usuarios.index'))
            ->assertForbidden();
    }

    public function test_administrador_puede_ver_modulos_de_seguridad(): void
    {
        $administrador = $this->crearAdministrador();

        $this->actingAs($administrador)
            ->get(route('seguridad.usuarios.index'))
            ->assertOk()
            ->assertSee('Usuarios del sistema')
            ->assertSee('Tipo')
            ->assertSee('Nuevo usuario');

        $this->actingAs($administrador)
            ->get(route('seguridad.roles.index'))
            ->assertOk()
            ->assertSee('Roles y permisos')
            ->assertSee('Nuevo rol');
    }

    public function test_paginador_de_usuarios_conserva_busqueda_activa(): void
    {
        $administrador = $this->crearAdministrador();

        foreach (range(1, 9) as $numero) {
            User::factory()->create([
                'name' => 'Paginado Usuario '.$numero,
                'email' => 'paginado'.$numero.'@hceapp.test',
                'activo' => true,
            ]);
        }

        Livewire::actingAs($administrador)
            ->test(UsuariosPanel::class)
            ->set('busqueda', 'Paginado')
            ->call('gotoPage', 2)
            ->assertSet('busqueda', 'Paginado')
            ->assertSee('Paginado Usuario 9')
            ->assertDontSee('Paginado Usuario 1');
    }

    public function test_componente_crea_usuario_con_rol(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(UsuariosPanel::class)
            ->call('crear')
            ->set('name', 'Medico de Guardia')
            ->set('email', 'medico@hceapp.test')
            ->set('password', 'ClaveSegura123')
            ->set('activo', true)
            ->set('rolesSeleccionados', ['Medico'])
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario = User::query()->where('email', 'medico@hceapp.test')->firstOrFail();

        $this->assertTrue($usuario->hasRole('Medico'));
        $this->assertTrue($usuario->activo);
    }

    public function test_componente_crea_usuario_profesional_de_salud_vinculado(): void
    {
        $administrador = $this->crearAdministrador();
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Claudia Ramos Guardia',
            'cmp' => '123456',
        ]);

        Livewire::actingAs($administrador)
            ->test(UsuariosPanel::class)
            ->call('crear')
            ->set('name', 'Claudia Ramos')
            ->set('email', 'claudia.ramos@hceapp.test')
            ->set('password', 'ClaveSegura123')
            ->set('tipoUsuario', 'Profesional de Salud')
            ->set('busquedaProfesional', '123456')
            ->assertSee('Claudia Ramos Guardia')
            ->call('seleccionarProfesional', $profesional->id)
            ->assertSet('profesionalSaludId', $profesional->id)
            ->assertSet('mostrarSugerenciasProfesional', false)
            ->set('rolesSeleccionados', ['Medico'])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'claudia.ramos@hceapp.test',
            'tipo_usuario' => 'Profesional de Salud',
            'profesional_salud_id' => $profesional->id,
        ]);
    }

    public function test_componente_edita_usuario_medico_y_autocompleta_profesional(): void
    {
        $administrador = $this->crearAdministrador();
        $usuario = User::factory()->create([
            'tipo_usuario' => 'Administrativo',
            'profesional_salud_id' => null,
        ]);
        $usuario->assignRole('Medico');
        $profesional = ProfesionalSalud::factory()->create([
            'nombres' => 'Mario Vargas Emergencias',
            'cmp' => '654321',
        ]);

        Livewire::actingAs($administrador)
            ->test(UsuariosPanel::class)
            ->call('editar', $usuario->id)
            ->set('tipoUsuario', 'Médico')
            ->set('busquedaProfesional', 'Vargas')
            ->assertSee('Mario Vargas Emergencias')
            ->call('seleccionarProfesional', $profesional->id)
            ->assertSet('profesionalSaludId', $profesional->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'tipo_usuario' => 'Médico',
            'profesional_salud_id' => $profesional->id,
        ]);
    }

    public function test_usuario_profesional_de_salud_requiere_profesional_vinculado(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(UsuariosPanel::class)
            ->call('crear')
            ->set('name', 'Usuario sin profesional')
            ->set('email', 'sin.profesional@hceapp.test')
            ->set('password', 'ClaveSegura123')
            ->set('tipoUsuario', 'Profesional de Salud')
            ->set('rolesSeleccionados', ['Medico'])
            ->call('guardar')
            ->assertHasErrors(['profesionalSaludId' => 'required']);
    }

    public function test_componente_crea_rol_con_permisos(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(RolesPermisosPanel::class)
            ->call('crear')
            ->set('name', 'Coordinador de Admision')
            ->set('permisosSeleccionados', [
                Permisos::DASHBOARD_VER,
                Permisos::PACIENTES_VER,
            ])
            ->call('guardar')
            ->assertHasNoErrors();

        $rol = Role::findByName('Coordinador de Admision');

        $this->assertTrue($rol->hasPermissionTo(Permisos::PACIENTES_VER));
        $this->assertCount(count(Permisos::nombres()), Permission::all());
    }

    private function crearAdministrador(): User
    {
        $this->seed(RolesPermisosSeeder::class);

        $administrador = User::factory()->create(['activo' => true]);
        $administrador->assignRole('Administrador');

        return $administrador;
    }
}
