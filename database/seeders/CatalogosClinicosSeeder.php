<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosClinicosSeeder extends Seeder
{
    /**
     * Carga catalogos clinicos minimos para iniciar el flujo de emergencia.
     */
    public function run(): void
    {
        $usuarioAdministradorId = User::query()->where('email', 'admin@hceapp.test')->value('id');

        $this->cargarPrioridades($usuarioAdministradorId);
        $this->cargarSignosVitales($usuarioAdministradorId);
        $this->cargarTiposOrdenes($usuarioAdministradorId);
        $this->cargarAlergias($usuarioAdministradorId);
        $this->cargarDiagnosticosCie10($usuarioAdministradorId);
        $this->cargarExamenes($usuarioAdministradorId);
        $this->cargarProcedimientos($usuarioAdministradorId);
        $this->cargarMedicamentos($usuarioAdministradorId);
    }

    private function cargarPrioridades(?int $usuarioId): void
    {
        $this->upsertConAuditoria('prioridades', [
            ['prioridad' => 'I.EMERGENCIA RIESGO VITAL', 'color' => 'Rojo', 'orden' => 1],
            ['prioridad' => 'II.URGENCIA MAYOR', 'color' => 'Naranja', 'orden' => 2],
            ['prioridad' => 'III.URGENCIA MENOR', 'color' => 'Amarillo', 'orden' => 3],
            ['prioridad' => 'IV.PATOLOGIA AGUDA COMUN', 'color' => 'Verde', 'orden' => 4],
        ], ['prioridad'], $usuarioId);
    }

    private function cargarSignosVitales(?int $usuarioId): void
    {
        $this->upsertConAuditoria('signos_vitales', [
            ['descripcion' => 'Temperatura', 'unidad' => '°C'],
            ['descripcion' => 'Frecuencia respiratoria', 'unidad' => 'resp/min'],
            ['descripcion' => 'Frecuencia cardiaca', 'unidad' => 'lat/min'],
            ['descripcion' => 'Presion arterial', 'unidad' => 'mmHg'],
            ['descripcion' => 'Saturacion de oxigeno', 'unidad' => '%'],
            ['descripcion' => 'Peso', 'unidad' => 'kg'],
            ['descripcion' => 'Talla', 'unidad' => 'm'],
            ['descripcion' => 'IMC', 'unidad' => 'kg/m2'],
        ], ['descripcion', 'unidad'], $usuarioId);
    }

    private function cargarTiposOrdenes(?int $usuarioId): void
    {
        $this->upsertConAuditoria('tipos_ordenes', [
            ['descripcion' => 'Laboratorio'],
            ['descripcion' => 'Imagen'],
            ['descripcion' => 'Interconsulta'],
            ['descripcion' => 'Transferencia interna'],
            ['descripcion' => 'Apoyo respiratorio'],
            ['descripcion' => 'Tratamiento'],
        ], ['descripcion'], $usuarioId);
    }

    private function cargarAlergias(?int $usuarioId): void
    {
        $this->upsertConAuditoria('alergias', [
            ['nombre' => 'Latex', 'tipo' => 'Contacto', 'descripcion' => 'Alergia o sensibilidad al latex'],
            ['nombre' => 'AINE', 'tipo' => 'Medicamento', 'descripcion' => 'Reaccion adversa asociada a antiinflamatorios'],
            ['nombre' => 'Penicilina', 'tipo' => 'Medicamento', 'descripcion' => 'Alergia a penicilinas'],
        ], ['nombre'], $usuarioId);
    }

    private function cargarDiagnosticosCie10(?int $usuarioId): void
    {
        $this->upsertConAuditoria('diagnosticos_cie10', [
            ['codigo' => 'K80.0', 'nombre' => 'Calculo de vesicula biliar con colecistitis aguda sin obstruccion', 'genero' => 'Ambos'],
            ['codigo' => 'E86.0', 'nombre' => 'Deshidratacion leve', 'genero' => 'Ambos'],
            ['codigo' => 'E87.6', 'nombre' => 'Hipopotasemia', 'genero' => 'Ambos'],
            ['codigo' => 'E11.9', 'nombre' => 'Diabetes mellitus tipo 2 sin complicaciones documentadas', 'genero' => 'Ambos'],
            ['codigo' => 'I10', 'nombre' => 'Hipertension arterial esencial', 'genero' => 'Ambos'],
        ], ['codigo'], $usuarioId);
    }

    private function cargarExamenes(?int $usuarioId): void
    {
        $this->upsertConAuditoria('examenes', [
            ['descripcion' => 'Hemograma'],
            ['descripcion' => 'Proteina C reactiva'],
            ['descripcion' => 'Perfil hepatico'],
            ['descripcion' => 'Creatinina'],
            ['descripcion' => 'Electrolitos'],
            ['descripcion' => 'Ecografia abdominal'],
            ['descripcion' => 'Electrocardiograma de 12 derivaciones'],
        ], ['descripcion'], $usuarioId);
    }

    private function cargarProcedimientos(?int $usuarioId): void
    {
        $this->upsertConAuditoria('procedimientos', [
            ['codigo' => '36000', 'denominacion' => 'Canalizacion de via venosa periferica'],
            ['codigo' => '36415', 'denominacion' => 'Toma de muestras de sangre para laboratorio'],
            ['codigo' => '76705', 'denominacion' => 'Ecografia abdominal enfocada'],
            ['codigo' => '93005', 'denominacion' => 'Electrocardiograma de 12 derivaciones'],
            ['codigo' => '96365', 'denominacion' => 'Infusion intravenosa de medicamento'],
        ], ['codigo'], $usuarioId);
    }

    private function cargarMedicamentos(?int $usuarioId): void
    {
        $this->upsertConAuditoria('medicamentos', [
            ['descripcion' => 'Cloruro de sodio 0.9 %', 'presentacion' => 'Bolsa 1000 mL', 'generico' => 'Cloruro de sodio'],
            ['descripcion' => 'Ceftriaxona', 'presentacion' => 'Vial 2 g', 'generico' => 'Ceftriaxona'],
            ['descripcion' => 'Metronidazol', 'presentacion' => 'Frasco 500 mg/100 mL', 'generico' => 'Metronidazol'],
            ['descripcion' => 'Paracetamol', 'presentacion' => 'Solucion 10 mg/mL', 'generico' => 'Paracetamol'],
            ['descripcion' => 'Morfina sulfato', 'presentacion' => 'Ampolla 10 mg/mL', 'generico' => 'Morfina sulfato'],
            ['descripcion' => 'Ondansetron', 'presentacion' => 'Ampolla 4 mg/2 mL', 'generico' => 'Ondansetron'],
        ], ['descripcion'], $usuarioId);
    }

    /**
     * Inserta o actualiza catalogos con campos comunes de auditoria.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<int, string>  $clavesUnicas
     */
    private function upsertConAuditoria(string $tabla, array $filas, array $clavesUnicas, ?int $usuarioId): void
    {
        $ahora = now();

        foreach ($filas as $fila) {
            $criterios = array_intersect_key($fila, array_flip($clavesUnicas));
            $existe = DB::table($tabla)->where($criterios)->exists();

            $datos = array_merge([
                'estado' => 'Activo',
                'actualizado_por' => $usuarioId,
                'updated_at' => $ahora,
            ], $fila);

            if ($existe) {
                DB::table($tabla)->where($criterios)->update($datos);

                continue;
            }

            DB::table($tabla)->insert(array_merge([
                'registrado_por' => $usuarioId,
                'created_at' => $ahora,
            ], $datos));
        }
    }
}
