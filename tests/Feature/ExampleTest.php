<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_ruta_principal_redirige_al_inicio_de_sesion(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
