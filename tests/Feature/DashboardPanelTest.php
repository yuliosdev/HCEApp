<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_autenticado_visualiza_panel_principal_con_controles_visuales(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create(['name' => 'Administrador']);
        $usuario->assignRole('Administrador');

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Panel principal HCEApp');
        $response->assertSee('Centro operativo de emergencias');
        $response->assertSee('fonts.bunny.net/css?family=inter', false);
        $response->assertSee('montserrat:600,700,800', false);
        $response->assertSee('poppins:500,600,700', false);
        $response->assertSee('data-sidebar-toggle', false);
        $response->assertSee('data-mobile-sidebar-open', false);
        $response->assertSee('data-mobile-sidebar-backdrop', false);
        $response->assertSee('data-menu-toggle="historia-clinica"', false);
        $response->assertSee('data-menu-leaf="pacientes"', false);
        $response->assertSee('data-menu-leaf="profesionales"', false);
        $response->assertSee('data-menu-leaf="dashboard"', false);
        $response->assertSee('data-menu-subitem="historia-clinica:atenciones-del-paciente"', false);
        $response->assertSee('data-menu-subitem="mantenimiento:procedimientos"', false);
        $response->assertSee('data-menu-parent="historia-clinica"', false);
        $response->assertSee('<iconify-icon', false);
        $response->assertSee('icon="lucide:house"', false);
        $response->assertSee('icon="lucide:clipboard-list"', false);
        $response->assertSee('icon="lucide:stethoscope"', false);
        $response->assertSee('icon="lucide:chevron-right"', false);
        $response->assertSee('Gestion Clinica');
        $response->assertSee('Historia Clinica');
        $response->assertSee('Profesionales');
        $response->assertSee('Procedimientos');
        $response->assertSee('CIE10');
        $response->assertSee('Examenes');
        $response->assertSee('Ambientes');
        $response->assertSee('Atenciones por Prioridad');
        $response->assertSee('Parametros Generales');
        $response->assertSee('data-dropdown-toggle', false);
        $response->assertSee('data-theme-current-icon="light"', false);
        $response->assertSee('data-theme-current-icon="dark"', false);
        $response->assertSee('data-theme-current-icon="system"', false);
        $response->assertSee('data-theme-option="light"', false);
        $response->assertSee('data-theme-option="dark"', false);
        $response->assertSee('data-theme-option="system"', false);
        $response->assertSee('data-count-target', false);
        $response->assertSee('Configuracion del usuario');
        $response->assertSee('Cerrar sesion');
        $response->assertDontSee('data-theme-cycle', false);
        $response->assertDontSee('data-theme-label', false);
        $response->assertDontSee('Paleta institucional clara');
        $response->assertDontSee('Paleta institucional oscura');
        $response->assertDontSee('Respeta el sistema operativo');
        $response->assertDontSee('<svg', false);
    }
}
