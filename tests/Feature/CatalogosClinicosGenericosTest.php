<?php

namespace Tests\Feature;

use App\Enums\EstadoCatalogo;
use App\Livewire\Catalogos\CatalogoClinicoPanel;
use App\Models\Ambiente;
use App\Models\Glasgow;
use App\Models\Medicamento;
use App\Models\SignoVital;
use App\Models\Ups;
use App\Models\User;
use App\Services\Catalogos\CatalogosClinicosArchivoService;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogosClinicosGenericosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_con_permiso_visualiza_todos_los_catalogos_genericos(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::CATALOGOS_VER);

        foreach ($this->rutasCatalogos() as $nombreRuta => $titulo) {
            $this->actingAs($usuario)
                ->get(route($nombreRuta))
                ->assertOk()
                ->assertSee($titulo)
                ->assertSee('Total registros')
                ->assertSee('Mostrar')
                ->assertSee('Acciones');
        }
    }

    public function test_herramientas_administrativas_aparecen_en_catalogos_genericos_solo_para_administrador(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::CATALOGOS_VER);
        $administrador = $this->crearAdministrador();

        $this->actingAs($usuario)
            ->get(route('catalogos.medicamentos.index'))
            ->assertOk()
            ->assertDontSee('Herramientas administrativas')
            ->assertDontSee('Gestion masiva de medicamentos');

        $this->actingAs($administrador)
            ->get(route('catalogos.medicamentos.index'))
            ->assertOk()
            ->assertSee('Herramientas administrativas')
            ->assertSee('Gestion masiva de medicamentos')
            ->assertSee('PDF')
            ->assertSee('XLSX')
            ->assertSee('Plantilla')
            ->assertSee('Importar');
    }

    public function test_paginador_de_catalogo_generico_usa_url_absoluta_correcta(): void
    {
        $administrador = $this->crearAdministrador();

        foreach (range(1, 6) as $numero) {
            Medicamento::query()->create([
                'descripcion' => 'Medicamento paginado '.$numero,
                'presentacion' => 'Tableta',
                'generico' => 'Generico '.$numero,
                'stock' => $numero,
                'estado' => EstadoCatalogo::Activo->value,
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ]);
        }

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->set('busqueda', 'paginado')
            ->assertSee(route('catalogos.medicamentos.index'), false)
            ->assertSee('busqueda=paginado', false)
            ->assertSee('page=2', false)
            ->assertDontSee('/catalogos/catalogos/medicamentos', false);
    }

    public function test_administrador_gestiona_catalogo_generico_de_medicamentos(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->call('abrirNuevo')
            ->set('form.descripcion', 'Ketorolaco')
            ->set('form.presentacion', 'Ampolla 30 mg/mL')
            ->set('form.generico', 'Ketorolaco')
            ->set('form.stock', 12)
            ->set('form.id_producto_externo', '')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $medicamento = Medicamento::query()->where('descripcion', 'Ketorolaco')->firstOrFail();

        $this->assertDatabaseHas('medicamentos', [
            'id' => $medicamento->id,
            'presentacion' => 'Ampolla 30 mg/mL',
            'stock' => 12,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->call('editar', $medicamento->id)
            ->set('form.presentacion', 'Tableta 10 mg')
            ->set('form.stock', 18)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('medicamentos', [
            'id' => $medicamento->id,
            'presentacion' => 'Tableta 10 mg',
            'stock' => 18,
        ]);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->call('cambiarEstado', $medicamento->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('medicamentos', [
            'id' => $medicamento->id,
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->call('eliminar', $medicamento->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('medicamentos', ['id' => $medicamento->id]);
    }

    public function test_administrador_gestiona_catalogos_ups_y_glasgow(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ups'])
            ->call('abrirNuevo')
            ->set('form.codigo', '999999')
            ->set('form.denominacion', 'UPS de prueba funcional')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $ups = Ups::query()->where('codigo', '999999')->firstOrFail();

        $this->assertTrue($ups->estado);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ups'])
            ->call('cambiarEstado', $ups->id)
            ->assertHasNoErrors();

        $this->assertFalse($ups->fresh()->estado);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'glasgow'])
            ->call('abrirNuevo')
            ->set('form.tipo', 'Ocular')
            ->set('form.puntaje', 4)
            ->set('form.descripcion', 'Apertura ocular espontanea')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('glasgow', [
            'tipo' => 'Ocular',
            'puntaje' => 4,
            'descripcion' => 'Apertura ocular espontanea',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
    }

    public function test_signos_vitales_guardan_rangos_normales_desde_modal(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'signos-vitales'])
            ->call('abrirNuevo')
            ->assertSee('Informacion General')
            ->assertSee('Configuracion de Rangos')
            ->set('form.descripcion', 'Presion sistolica')
            ->set('form.unidad', 'mmHg')
            ->set('form.rango_inicial', '90.00')
            ->set('form.rango_final', '120.00')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $signoVital = SignoVital::query()->where('descripcion', 'Presion sistolica')->firstOrFail();

        $this->assertSame('90.00', $signoVital->rango_inicial);
        $this->assertSame('120.00', $signoVital->rango_final);
    }

    public function test_administrador_ve_auditoria_en_modal_generico(): void
    {
        $administrador = $this->crearAdministrador();
        $ambiente = Ambiente::query()->create([
            'descripcion' => 'Observacion pediatrica',
            'ubicacion' => 'Modulo pediatrico',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ambientes'])
            ->call('ver', $ambiente->id)
            ->assertSee('Auditoria del registro')
            ->assertSee($administrador->name)
            ->assertSee($administrador->email);
    }

    public function test_solo_administrador_descarga_exportaciones_y_plantilla_de_catalogo_generico(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::CATALOGOS_VER);
        $administrador = $this->crearAdministrador();

        Medicamento::query()->create([
            'descripcion' => 'Medicamento para exportar',
            'presentacion' => 'Tableta',
            'generico' => 'Generico exportable',
            'stock' => 5,
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('catalogos.medicamentos.exportar.xlsx'))
            ->assertForbidden();

        $this->actingAs($administrador)
            ->get(route('catalogos.medicamentos.exportar.xlsx'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($administrador)
            ->get(route('catalogos.medicamentos.plantilla'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($administrador)
            ->get(route('catalogos.medicamentos.exportar.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_servicio_importa_catalogo_generico_desde_plantilla_excel(): void
    {
        $administrador = $this->crearAdministrador();
        $servicio = app(CatalogosClinicosArchivoService::class);
        $rutaPlantilla = $servicio->generarPlantilla('medicamentos');
        $archivo = new UploadedFile(
            $rutaPlantilla,
            'plantilla_medicamentos.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = $servicio->importar('medicamentos', $archivo, $administrador->id);

        @unlink($rutaPlantilla);

        $this->assertSame(2, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('medicamentos', [
            'descripcion' => 'Descripcion ejemplo 1',
            'stock' => 1,
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function rutasCatalogos(): array
    {
        return [
            'catalogos.cie10.index' => 'CIE10',
            'catalogos.medicamentos.index' => 'Medicamentos',
            'catalogos.ups.index' => 'UPS',
            'catalogos.examenes.index' => 'Examenes',
            'catalogos.especialidades.index' => 'Especialidades',
            'catalogos.signos-vitales.index' => 'Signos Vitales',
            'catalogos.glasgow.index' => 'Glasgow',
            'catalogos.alergias.index' => 'Alergias',
            'catalogos.tipo-ordenes.index' => 'Tipo de Ordenes',
            'catalogos.prioridades.index' => 'Prioridades',
            'catalogos.ambientes.index' => 'Ambientes',
        ];
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
