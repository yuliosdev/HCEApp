<?php

namespace Tests\Feature;

use App\Mail\IntentoAccesoBloqueadoMail;
use App\Models\User;
use App\Notifications\RestablecerContrasenaNotification;
use Database\Seeders\UsuarioAdministradorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
        $response->assertSee('Ingreso al sistema');
        $response->assertDontSee('Estados de atencion');
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

    public function test_quinto_intento_fallido_bloquea_temporalmente_y_envia_correo(): void
    {
        Mail::fake();
        $this->seed(UsuarioAdministradorSeeder::class);

        for ($intento = 1; $intento <= 5; $intento++) {
            $response = $this->from(route('login'))->post(route('login.guardar'), [
                'email' => 'admin@hceapp.test',
                'password' => 'clave-incorrecta',
            ]);
        }

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        Mail::assertSent(IntentoAccesoBloqueadoMail::class, function (IntentoAccesoBloqueadoMail $correo): bool {
            return $correo->hasTo('admin@hceapp.test')
                && $correo->minutosBloqueo === 5;
        });
    }

    public function test_puede_solicitar_enlace_de_recuperacion_de_contrasena(): void
    {
        Notification::fake();
        $usuario = User::factory()->create(['email' => 'usuario@hceapp.test']);

        $response = $this->post(route('password.email'), [
            'email' => $usuario->email,
        ]);

        $response->assertSessionHasNoErrors();
        Notification::assertSentTo($usuario, RestablecerContrasenaNotification::class);
    }

    public function test_puede_restablecer_contrasena_con_token_valido(): void
    {
        $usuario = User::factory()->create(['email' => 'usuario@hceapp.test']);
        $token = Password::createToken($usuario);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'NuevaClaveSegura123',
            'password_confirmation' => 'NuevaClaveSegura123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NuevaClaveSegura123', $usuario->fresh()->password));
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
