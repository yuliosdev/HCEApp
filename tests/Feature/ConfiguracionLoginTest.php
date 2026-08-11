<?php

namespace Tests\Feature;

use App\Models\ConfiguracionSistema;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_autenticado_puede_ver_configuracion_del_login(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create();
        $usuario->assignRole('Administrador');

        $response = $this->actingAs($usuario)->get(route('configuracion.login.editar'));

        $response->assertOk();
        $response->assertSee('Identidad visual del login');
    }

    public function test_usuario_autenticado_puede_actualizar_textos_del_login(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create();
        $usuario->assignRole('Administrador');

        $response = $this->actingAs($usuario)->put(route('configuracion.login.actualizar'), [
            'nombre_sistema' => 'HCE Emergencias',
            'titulo_login' => 'Acceso clinico seguro',
            'subtitulo_login' => 'Emergencia hospitalaria',
            'texto_institucional_login' => 'Sistema institucional de prueba.',
        ]);

        $response->assertRedirect(route('configuracion.login.editar'));

        $this->assertDatabaseHas('configuraciones_sistema', [
            'nombre_sistema' => 'HCE Emergencias',
            'titulo_login' => 'Acceso clinico seguro',
            'actualizado_por' => $usuario->id,
        ]);

        $this->assertSame('HCE Emergencias', ConfiguracionSistema::actual()->nombre_sistema);
    }
}
