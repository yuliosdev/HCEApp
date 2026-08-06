<?php

namespace Tests\Feature;

use App\Livewire\TestTecnologias\PanelTecnologias;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TestTecnologiasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_ruta_de_tecnologias_requiere_autenticacion(): void
    {
        $response = $this->get(route('test.tecnologias'));

        $response->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_puede_ver_test_de_tecnologias(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('test.tecnologias'));

        $response->assertOk();
        $response->assertSee('Test de tecnologias frontend');
        $response->assertSee('Tailwind CSS 4');
        $response->assertSee('Abrir modal');
        $response->assertSee('Abrir drawer');
    }

    public function test_componente_livewire_filtra_pagina_y_notifica(): void
    {
        Livewire::test(PanelTecnologias::class)
            ->assertSee('Tabla paginada de atenciones simuladas')
            ->assertSee('Ana Torres Medina')
            ->set('busqueda', 'Shock')
            ->assertSee('Shock trauma')
            ->call('nextPage')
            ->call('emitirNotificacion', 'flash')
            ->call('emitirNotificacion', 'toastr')
            ->call('emitirNotificacion', 'noty')
            ->call('emitirNotificacion', 'sweetalert');
    }

    public function test_formulario_livewire_valida_y_envia_desde_modal(): void
    {
        Livewire::test(PanelTecnologias::class)
            ->set('form.paciente', 'Paciente Validado')
            ->set('form.documento', '99887766')
            ->set('form.prioridad', 'Alta')
            ->set('form.adaptador', 'toastr')
            ->set('form.observacion', 'Validacion automatizada del modal.')
            ->call('registrarDesdeModal')
            ->assertHasNoErrors();
    }
}
