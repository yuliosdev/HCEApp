<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogosClinicosSeeder extends Seeder
{
    /**
     * Carga catalogos clinicos minimos para iniciar el flujo de emergencia.
     */
    public function run(): void
    {
        $usuarioAdministradorId = User::query()->where('email', 'admin@hceapp.test')->value('id');

        $this->cargarPrioridades($usuarioAdministradorId);
        $this->cargarUps($usuarioAdministradorId);
        $this->cargarGlasgow($usuarioAdministradorId);
        $this->cargarSignosVitales($usuarioAdministradorId);
        $this->cargarTiposOrdenes($usuarioAdministradorId);
        $this->cargarEspecialidades($usuarioAdministradorId);
        $this->cargarAmbientes($usuarioAdministradorId);
        $this->cargarAlergias($usuarioAdministradorId);
        $this->cargarDiagnosticosCie10($usuarioAdministradorId);
        $this->cargarExamenes($usuarioAdministradorId);
        $this->cargarProcedimientos($usuarioAdministradorId);
        $this->cargarMedicamentos($usuarioAdministradorId);
    }

    private function cargarPrioridades(?int $usuarioId): void
    {
        $this->upsertConAuditoria('prioridades', [
            ['prioridad' => 'I.EMERGENCIA RIESGO VITAL', 'color' => 'Rojo', 'orden' => 1, 'tiempo_atencion' => 0],
            ['prioridad' => 'II.URGENCIA MAYOR', 'color' => 'Naranja', 'orden' => 2, 'tiempo_atencion' => 10],
            ['prioridad' => 'III.URGENCIA MENOR', 'color' => 'Amarillo', 'orden' => 3, 'tiempo_atencion' => 60],
            ['prioridad' => 'IV.PATOLOGIA AGUDA COMUN', 'color' => 'Verde', 'orden' => 4, 'tiempo_atencion' => 120],
        ], ['prioridad'], $usuarioId);
    }

    private function cargarUps(?int $usuarioId): void
    {
        $ruta = database_path('seeders/data/ups.json');
        $ups = json_decode(file_get_contents($ruta), true, flags: JSON_THROW_ON_ERROR);

        $this->upsertConAuditoria('ups', $ups, ['codigo'], $usuarioId);
    }

    private function cargarGlasgow(?int $usuarioId): void
    {
        $this->upsertConAuditoria('glasgow', [
            ['tipo' => 'Ocular', 'puntaje' => 1, 'descripcion' => 'No apertura ocular'],
            ['tipo' => 'Ocular', 'puntaje' => 2, 'descripcion' => 'Apertura ocular al dolor'],
            ['tipo' => 'Ocular', 'puntaje' => 3, 'descripcion' => 'Apertura ocular al llamado'],
            ['tipo' => 'Ocular', 'puntaje' => 4, 'descripcion' => 'Apertura ocular espontanea'],
            ['tipo' => 'Verbal', 'puntaje' => 1, 'descripcion' => 'Sin respuesta verbal'],
            ['tipo' => 'Verbal', 'puntaje' => 2, 'descripcion' => 'Sonidos incomprensibles'],
            ['tipo' => 'Verbal', 'puntaje' => 3, 'descripcion' => 'Palabras inapropiadas'],
            ['tipo' => 'Verbal', 'puntaje' => 4, 'descripcion' => 'Respuesta verbal confusa'],
            ['tipo' => 'Verbal', 'puntaje' => 5, 'descripcion' => 'Respuesta verbal orientada'],
            ['tipo' => 'Motora', 'puntaje' => 1, 'descripcion' => 'Sin respuesta motora'],
            ['tipo' => 'Motora', 'puntaje' => 2, 'descripcion' => 'Extension anormal al dolor'],
            ['tipo' => 'Motora', 'puntaje' => 3, 'descripcion' => 'Flexion anormal al dolor'],
            ['tipo' => 'Motora', 'puntaje' => 4, 'descripcion' => 'Retirada ante estimulo doloroso'],
            ['tipo' => 'Motora', 'puntaje' => 5, 'descripcion' => 'Localiza el dolor'],
            ['tipo' => 'Motora', 'puntaje' => 6, 'descripcion' => 'Obedece ordenes'],
        ], ['tipo', 'puntaje'], $usuarioId, Schema::hasColumn('glasgow', 'estado'));
    }

    private function cargarSignosVitales(?int $usuarioId): void
    {
        $this->upsertConAuditoria('signos_vitales', [
            ['descripcion' => 'Temperatura', 'unidad' => '°C', 'favorito' => true],
            ['descripcion' => 'Frecuencia respiratoria', 'unidad' => 'resp/min', 'favorito' => true],
            ['descripcion' => 'Frecuencia cardiaca', 'unidad' => 'lat/min', 'favorito' => true],
            ['descripcion' => 'Presión arterial Sistólica (PAS)', 'unidad' => 'mmHg', 'favorito' => true],
            ['descripcion' => 'Presión arterial Diastólica (PAD)', 'unidad' => 'mmHg', 'favorito' => true],
            ['descripcion' => 'Saturacion de oxigeno', 'unidad' => '%', 'favorito' => true],
            ['descripcion' => 'Peso', 'unidad' => 'kg', 'favorito' => false],
            ['descripcion' => 'Talla', 'unidad' => 'm', 'favorito' => false],
            ['descripcion' => 'IMC', 'unidad' => 'kg/m2', 'favorito' => false],
        ], ['descripcion', 'unidad'], $usuarioId);
    }

    private function cargarTiposOrdenes(?int $usuarioId): void
    {
        $this->upsertConAuditoria('tipos_ordenes', [
            ['descripcion' => 'Imagen'],
            ['descripcion' => 'Procedimiento'],
            ['descripcion' => 'Interconsulta'],
            ['descripcion' => 'Transferencia interna'],
            ['descripcion' => 'Apoyo respiratorio'],
            ['descripcion' => 'Tratamiento'],
            ['descripcion' => 'Otros'],
        ], ['descripcion'], $usuarioId);
    }

    private function cargarEspecialidades(?int $usuarioId): void
    {
        $this->upsertConAuditoria('especialidades', [
            ['nombre' => 'Medicina de Emergencias', 'descripcion' => 'Atencion medica inicial de emergencias'],
            ['nombre' => 'Cirugia General', 'descripcion' => 'Evaluacion quirurgica general'],
            ['nombre' => 'Medicina Interna', 'descripcion' => 'Evaluacion clinica especializada'],
            ['nombre' => 'Pediatria', 'descripcion' => 'Atencion de pacientes pediatricos'],
            ['nombre' => 'Ginecologia y Obstetricia', 'descripcion' => 'Atencion gineco obstetrica'],
        ], ['nombre'], $usuarioId);
    }

    private function cargarAmbientes(?int $usuarioId): void
    {
        $this->upsertConAuditoria('ambientes', [
            ['denominacion' => 'Topico Cama 01'],
            ['denominacion' => 'Topico Cama 02'],
            ['denominacion' => 'Emergencia Cama 01'],
            ['denominacion' => 'Emergencia Cama 02'],
            ['denominacion' => 'Emergencia Cama 03'],
            ['denominacion' => 'Emergencia Cama 04'],
            ['denominacion' => 'Emergencia Cama 05'],
            ['denominacion' => 'Emergencia Cama 06'],
            ['denominacion' => 'Shock trauma'],
        ], ['denominacion'], $usuarioId);
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
            ['codigo' => 'I10', 'nombre' => 'Hipertension arterial esencial', 'genero' => 'Ambos'],
        ], ['codigo'], $usuarioId);
    }

    private function cargarExamenes(?int $usuarioId): void
    {
        $this->upsertConAuditoria('examenes', [
            ['descripcion' => 'QUIMICA SANGUINEA'],
        ], ['descripcion'], $usuarioId);
    }

    private function cargarProcedimientos(?int $usuarioId): void
    {
        $this->upsertConAuditoria('procedimientos', [
            ['codigo' => '00000', 'denominacion' => 'GASTOS CLINICA'],
        ], ['codigo'], $usuarioId);
    }

    private function cargarMedicamentos(?int $usuarioId): void
    {
        $this->upsertConAuditoria('medicamentos', [
            ['descripcion' => 'Ondansetron', 'presentacion' => 'Ampolla 4 mg/2 mL', 'generico' => 'Ondansetron', 'stock' => 45],
        ], ['descripcion'], $usuarioId);
    }

    /**
     * Inserta o actualiza catalogos con campos comunes de auditoria.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @param  array<int, string>  $clavesUnicas
     */
    private function upsertConAuditoria(string $tabla, array $filas, array $clavesUnicas, ?int $usuarioId, bool $incluyeEstado = true): void
    {
        $ahora = now();

        foreach ($filas as $fila) {
            $criterios = array_intersect_key($fila, array_flip($clavesUnicas));
            $existe = DB::table($tabla)->where($criterios)->exists();

            $datos = array_merge([
                'actualizado_por' => $usuarioId,
                'updated_at' => $ahora,
            ], $incluyeEstado ? ['estado' => 'Activo'] : [], $fila);

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
