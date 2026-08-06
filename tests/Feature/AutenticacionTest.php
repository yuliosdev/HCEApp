<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UsuarioAdministradorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_puede_ver_la_pantalla_de_inicio_de_sesion(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Iniciar sesion');
    }

    public function test_usuario_administrador_puede_iniciar_sesion(): void
    {
        $this->seed(UsuarioAdministradorSeeder::class);

        $response = $this->post(route('login.guardar'), [
            'email' => 'admin@hceapp.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    public function test_credenciales_invalidas_no_inician_sesion(): void
    {
        $this->seed(UsuarioAdministradorSeeder::class);

        $response = $this->from(route('login'))->post(route('login.guardar'), [
            'email' => 'admin@hceapp.test',
            'password' => 'clave-incorrecta',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_puede_cerrar_sesion(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
