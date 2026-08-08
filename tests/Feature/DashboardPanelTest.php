<?php

namespace Tests\Feature;

use App\Models\User;
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
        $usuario = User::factory()->create(['name' => 'Administrador']);

        $response = $this->actingAs($usuario)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Panel principal HCEApp');
        $response->assertSee('Centro operativo de emergencias');
        $response->assertSee('data-sidebar-toggle', false);
        $response->assertSee('data-mobile-sidebar-open', false);
        $response->assertSee('data-mobile-sidebar-backdrop', false);
        $response->assertSee('data-menu-toggle="emergencias"', false);
        $response->assertSee('data-menu-leaf="dashboard"', false);
        $response->assertSee('data-menu-subitem="emergencias:bandeja-de-emergencia"', false);
        $response->assertSee('data-menu-parent="emergencias"', false);
        $response->assertSee('<iconify-icon', false);
        $response->assertSee('icon="lucide:house"', false);
        $response->assertSee('icon="lucide:briefcase-medical"', false);
        $response->assertSee('icon="lucide:chevron-right"', false);
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
