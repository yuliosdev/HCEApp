<?php

namespace Tests\Feature;

use App\Enums\EstadoCatalogo;
use App\Livewire\Catalogos\CatalogoClinicoPanel;
use App\Models\Ambiente;
use App\Models\HcEmergencia;
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
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
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

    public function test_importacion_cie10_usa_proceso_directo_por_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'cie10_livewire_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['codigo', 'nombre', 'genero', 'estado']));
        $writer->addRow(Row::fromValues([
            'Z999',
            'Diagnostico desde panel',
            'Ambos',
            EstadoCatalogo::Activo->value,
        ]));
        $writer->close();

        $archivo = UploadedFile::fake()
            ->createWithContent('cie10_panel.xlsx', file_get_contents($ruta))
            ->mimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'cie10'])
            ->assertDontSee('Estamos procesando el Excel. Mantén esta pantalla abierta hasta que termine.')
            ->assertDontSee('El archivo puede tardar si contiene miles de filas. No cierres ni actualices esta pantalla.')
            ->set('archivoImportacion', $archivo)
            ->call('importarDesdeExcel')
            ->assertSet('archivoImportacion', null);

        @unlink($ruta);

        $this->assertDatabaseHas('diagnosticos_cie10', [
            'codigo' => 'Z999',
            'nombre' => 'Diagnostico desde panel',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
        ]);
    }

    public function test_importacion_medicamentos_usa_proceso_directo_por_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'medicamentos_livewire_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['descripcion', 'presentacion', 'generico', 'stock', 'id_producto_externo', 'estado']));
        $writer->addRow(Row::fromValues([
            'Paracetamol desde panel',
            'Tableta 500 mg',
            'Paracetamol',
            100,
            9001,
            EstadoCatalogo::Activo->value,
        ]));
        $writer->close();

        $archivo = UploadedFile::fake()
            ->createWithContent('medicamentos_panel.xlsx', file_get_contents($ruta))
            ->mimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'medicamentos'])
            ->set('archivoImportacion', $archivo)
            ->call('importarDesdeExcel')
            ->assertSet('archivoImportacion', null);

        @unlink($ruta);

        $this->assertDatabaseHas('medicamentos', [
            'descripcion' => 'Paracetamol desde panel',
            'presentacion' => 'Tableta 500 mg',
            'stock' => 100,
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
        ]);
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
            ->set('form.favorito', true)
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $signoVital = SignoVital::query()->where('descripcion', 'Presion sistolica')->firstOrFail();

        $this->assertSame('90.00', $signoVital->rango_inicial);
        $this->assertSame('120.00', $signoVital->rango_final);
        $this->assertTrue($signoVital->favorito);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'signos-vitales'])
            ->call('editar', $signoVital->id)
            ->assertSet('form.favorito', '1')
            ->set('form.favorito', '0')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertFalse($signoVital->fresh()->favorito);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'signos-vitales'])
            ->call('ver', $signoVital->id)
            ->assertSet('form.favorito', '0');
    }

    public function test_administrador_ve_auditoria_en_modal_generico(): void
    {
        $administrador = $this->crearAdministrador();
        $ambiente = Ambiente::query()->create([
            'denominacion' => 'Observacion pediatrica',
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

    public function test_administrador_gestiona_catalogo_generico_de_ambientes(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ambientes'])
            ->call('abrirNuevo')
            ->set('form.denominacion', 'Observacion respiratoria')
            ->call('guardar')
            ->assertHasNoErrors();

        $ambiente = Ambiente::query()->where('denominacion', 'Observacion respiratoria')->firstOrFail();

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ambientes'])
            ->call('editar', $ambiente->id)
            ->set('form.denominacion', 'Observacion respiratoria adultos')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ambientes', [
            'id' => $ambiente->id,
            'denominacion' => 'Observacion respiratoria adultos',
        ]);
    }

    public function test_ambiente_usado_en_historia_clinica_no_se_elimina(): void
    {
        $administrador = $this->crearAdministrador();
        $ambiente = Ambiente::factory()->create(['denominacion' => 'Ambiente con uso clinico']);
        HcEmergencia::factory()->create(['ambiente_id' => $ambiente->id]);

        Livewire::actingAs($administrador)
            ->test(CatalogoClinicoPanel::class, ['catalogo' => 'ambientes'])
            ->call('eliminar', $ambiente->id);

        $this->assertNotSoftDeleted('ambientes', ['id' => $ambiente->id]);
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

    public function test_servicio_importa_cie10_masivamente_por_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'cie10_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['codigo', 'nombre', 'genero', 'estado']));

        foreach (range(1, 9000) as $numero) {
            $writer->addRow(Row::fromValues([
                'T'.$numero,
                'Diagnostico masivo '.$numero,
                'Ambos',
                EstadoCatalogo::Activo->value,
            ]));
        }

        $writer->close();

        $archivo = new UploadedFile(
            $ruta,
            'cie10_masivo.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = app(CatalogosClinicosArchivoService::class)
            ->importar('cie10', $archivo, $administrador->id);

        @unlink($ruta);

        $this->assertSame(9000, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertSame(0, $resultado['omitidos']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('diagnosticos_cie10', [
            'codigo' => 'T9000',
            'nombre' => 'Diagnostico masivo 9000',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
        ]);
    }

    public function test_servicio_importa_medicamentos_masivamente_por_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'medicamentos_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['descripcion', 'presentacion', 'generico', 'stock', 'id_producto_externo', 'estado']));

        foreach (range(1, 1500) as $numero) {
            $writer->addRow(Row::fromValues([
                'Medicamento masivo '.$numero,
                'Presentacion '.$numero,
                'Generico '.$numero,
                $numero,
                100000 + $numero,
                EstadoCatalogo::Activo->value,
            ]));
        }

        $writer->close();

        $archivo = new UploadedFile(
            $ruta,
            'medicamentos_masivo.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = app(CatalogosClinicosArchivoService::class)
            ->importar('medicamentos', $archivo, $administrador->id);

        @unlink($ruta);

        $this->assertSame(1500, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertSame(0, $resultado['omitidos']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('medicamentos', [
            'descripcion' => 'Medicamento masivo 1500',
            'presentacion' => 'Presentacion 1500',
            'stock' => 1500,
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
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
