<?php

namespace Tests\Feature;

use App\Enums\EstadoCatalogo;
use App\Livewire\Catalogos\ProcedimientosPanel;
use App\Models\Procedimiento;
use App\Models\User;
use App\Services\Catalogos\ProcedimientosArchivoService;
use App\Support\Seguridad\Permisos;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class CatalogosProcedimientosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_usuario_sin_permiso_no_puede_ver_catalogo_de_procedimientos(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = User::factory()->create(['activo' => true]);

        $this->actingAs($usuario)
            ->get(route('catalogos.procedimientos.index'))
            ->assertForbidden();
    }

    public function test_usuario_con_permiso_visualiza_catalogo_de_procedimientos(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::CATALOGOS_VER);

        Procedimiento::query()->create([
            'codigo' => 'PROC-001',
            'denominacion' => 'Curacion de herida simple',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $usuario->id,
            'actualizado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('catalogos.procedimientos.index'))
            ->assertOk()
            ->assertSee('Procedimientos')
            ->assertSee('Total registros')
            ->assertSee('Buscar por codigo o denominacion')
            ->assertSee('Mostrar')
            ->assertSee('5 registros')
            ->assertSee('Curacion de herida simple')
            ->assertSee('Acciones')
            ->assertDontSee('Herramientas administrativas');
    }

    public function test_administrador_visualiza_herramientas_masivas_del_catalogo(): void
    {
        $administrador = $this->crearAdministrador();

        $this->actingAs($administrador)
            ->get(route('catalogos.procedimientos.index'))
            ->assertOk()
            ->assertSee('Herramientas administrativas')
            ->assertSee('PDF')
            ->assertSee('XLSX')
            ->assertSee('Plantilla')
            ->assertSee('Importar');
    }

    public function test_importacion_de_procedimientos_desde_panel_usa_lotes_y_finaliza_con_alerta(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'procedimientos_livewire_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['codigo', 'denominacion', 'estado']));
        $writer->addRow(Row::fromValues([
            'PROC-PANEL-001',
            'Procedimiento importado desde panel',
            EstadoCatalogo::Activo->value,
        ]));
        $writer->close();

        $archivo = UploadedFile::fake()
            ->createWithContent('procedimientos_panel.xlsx', file_get_contents($ruta))
            ->mimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->set('archivoImportacion', $archivo)
            ->call('importarDesdeExcel')
            ->assertSet('archivoImportacion', null);

        @unlink($ruta);

        $this->assertDatabaseHas('procedimientos', [
            'codigo' => 'PROC-PANEL-001',
            'denominacion' => 'Procedimiento importado desde panel',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
        ]);
    }

    public function test_administrador_crea_edita_cambia_estado_y_elimina_procedimiento(): void
    {
        $administrador = $this->crearAdministrador();

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->call('abrirNuevo')
            ->set('codigo', 'PROC-777')
            ->set('denominacion', 'Procedimiento inicial de prueba')
            ->set('estadoRegistro', EstadoCatalogo::Activo->value)
            ->call('guardar')
            ->assertHasNoErrors();

        $procedimiento = Procedimiento::query()->where('codigo', 'PROC-777')->firstOrFail();

        $this->assertDatabaseHas('procedimientos', [
            'id' => $procedimiento->id,
            'denominacion' => 'Procedimiento inicial de prueba',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->call('editar', $procedimiento->id)
            ->set('denominacion', 'Procedimiento actualizado de prueba')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('procedimientos', [
            'id' => $procedimiento->id,
            'denominacion' => 'Procedimiento actualizado de prueba',
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->call('cambiarEstado', $procedimiento->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('procedimientos', [
            'id' => $procedimiento->id,
            'estado' => EstadoCatalogo::Inactivo->value,
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->call('eliminar', $procedimiento->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('procedimientos', ['id' => $procedimiento->id]);
    }

    public function test_busqueda_filtra_los_procedimientos_por_codigo_o_denominacion(): void
    {
        $administrador = $this->crearAdministrador();

        Procedimiento::query()->create([
            'codigo' => 'LAB-100',
            'denominacion' => 'Toma de muestra',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Procedimiento::query()->create([
            'codigo' => 'IMG-200',
            'denominacion' => 'Radiografia simple',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->set('busqueda', 'radiografia')
            ->assertSee('Radiografia simple')
            ->assertDontSee('Toma de muestra');
    }

    public function test_las_acciones_se_conservan_al_aplicar_busqueda_y_filtro_de_estado(): void
    {
        $administrador = $this->crearAdministrador();

        Procedimiento::query()->create([
            'codigo' => 'ACC-100',
            'denominacion' => 'Procedimiento con acciones visibles',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->set('busqueda', 'acciones visibles')
            ->assertSee('lucide:eye')
            ->assertSee('lucide:pencil')
            ->assertSee('lucide:pause-circle')
            ->assertSee('lucide:trash-2')
            ->set('estado', EstadoCatalogo::Activo->value)
            ->assertSee('lucide:eye')
            ->assertSee('lucide:pencil')
            ->assertSee('lucide:pause-circle')
            ->assertSee('lucide:trash-2');
    }

    public function test_catalogo_inicia_mostrando_cinco_registros_y_permite_cambiar_cantidad(): void
    {
        $administrador = $this->crearAdministrador();

        foreach (range(1, 6) as $numero) {
            Procedimiento::query()->create([
                'codigo' => 'PAG-'.str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'denominacion' => 'Procedimiento paginado '.$numero,
                'estado' => EstadoCatalogo::Activo->value,
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ]);
        }

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->assertSet('porPagina', 5)
            ->assertSee('catalog-page-link', false)
            ->assertSee(route('catalogos.procedimientos.index', ['page' => 2]), false)
            ->assertSee('Procedimiento paginado 1')
            ->assertSee('Procedimiento paginado 5')
            ->assertDontSee('Procedimiento paginado 6')
            ->set('busqueda', 'paginado')
            ->assertSee('busqueda=paginado', false)
            ->assertSee('page=2', false)
            ->set('porPagina', 10)
            ->assertSee('Procedimiento paginado 6');
    }

    public function test_solo_administrador_descarga_exportaciones_y_plantilla(): void
    {
        $usuario = $this->crearUsuarioConPermiso(Permisos::CATALOGOS_VER);
        $administrador = $this->crearAdministrador();

        Procedimiento::query()->create([
            'codigo' => 'PDF-100',
            'denominacion' => 'Procedimiento para exportar',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        $this->actingAs($usuario)
            ->get(route('catalogos.procedimientos.exportar.xlsx'))
            ->assertForbidden();

        $this->actingAs($administrador)
            ->get(route('catalogos.procedimientos.exportar.xlsx'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($administrador)
            ->get(route('catalogos.procedimientos.plantilla'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($administrador)
            ->get(route('catalogos.procedimientos.exportar.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_servicio_importa_procedimientos_desde_plantilla_excel(): void
    {
        $administrador = $this->crearAdministrador();
        $servicio = app(ProcedimientosArchivoService::class);
        $rutaPlantilla = $servicio->generarPlantilla();
        $archivo = new UploadedFile(
            $rutaPlantilla,
            'plantilla_procedimientos.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = $servicio->importar($archivo, $administrador->id);

        @unlink($rutaPlantilla);

        $this->assertSame(2, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('procedimientos', [
            'codigo' => 'PROC-001',
            'denominacion' => 'Curacion de herida simple',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);
    }

    public function test_servicio_importa_procedimientos_masivamente_por_lotes(): void
    {
        $administrador = $this->crearAdministrador();
        $ruta = tempnam(sys_get_temp_dir(), 'procedimientos_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues(['codigo', 'denominacion', 'estado']));

        foreach (range(1, 1500) as $numero) {
            $writer->addRow(Row::fromValues([
                'MAS-'.$numero,
                'Procedimiento masivo '.$numero,
                EstadoCatalogo::Activo->value,
            ]));
        }

        $writer->close();

        $archivo = new UploadedFile(
            $ruta,
            'procedimientos_masivo.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $resultado = app(ProcedimientosArchivoService::class)
            ->importar($archivo, $administrador->id);

        @unlink($ruta);

        $this->assertSame(1500, $resultado['creados']);
        $this->assertSame(0, $resultado['actualizados']);
        $this->assertSame(0, $resultado['omitidos']);
        $this->assertSame([], $resultado['errores']);
        $this->assertDatabaseHas('procedimientos', [
            'codigo' => 'MAS-1500',
            'denominacion' => 'Procedimiento masivo 1500',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
        ]);
    }

    public function test_administrador_ve_auditoria_en_modal_de_procedimiento(): void
    {
        $administrador = $this->crearAdministrador();
        $procedimiento = Procedimiento::query()->create([
            'codigo' => 'AUD-100',
            'denominacion' => 'Procedimiento con auditoria',
            'estado' => EstadoCatalogo::Activo->value,
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ]);

        Livewire::actingAs($administrador)
            ->test(ProcedimientosPanel::class)
            ->call('ver', $procedimiento->id)
            ->assertSee('Auditoria del registro')
            ->assertSee($administrador->name)
            ->assertSee($administrador->email);
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
