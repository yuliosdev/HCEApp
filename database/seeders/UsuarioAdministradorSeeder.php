<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\ProfesionalSalud;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioAdministradorSeeder extends Seeder
{
    /**
     * Crea los usuarios administradores iniciales para el entorno local.
     */
    public function run(): void
    {
        $rolAdministrador = Role::findOrCreate('Administrador', 'web');

        $administrador = User::query()->updateOrCreate(
            ['email' => 'admin@hceapp.test'],
            [
                'name' => 'Administrador HCEApp',
                'tipo_usuario' => 'Supervisor',
                'profesional_salud_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'activo' => true,
            ],
        );

        $administrador->assignRole($rolAdministrador);
        $administrador->forceFill([
            'registrado_por' => $administrador->id,
            'actualizado_por' => $administrador->id,
        ])->save();

        $julio = User::query()->updateOrCreate(
            ['email' => 'yuliostch@gmail.com'],
            [
                'name' => 'Julio Tavara',
                'tipo_usuario' => 'Supervisor',
                'profesional_salud_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('Inicio123'),
                'activo' => true,
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ],
        );

        $julio->assignRole($rolAdministrador);

        $especialidad = Especialidad::query()->updateOrCreate(
            ['nombre' => 'Medicina de Emergencias'],
            [
                'descripcion' => 'Atencion medica inicial de emergencias',
                'estado' => 'Activo',
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ],
        );

        $profesional = ProfesionalSalud::query()->updateOrCreate(
            ['cmp' => '000001'],
            [
                'nombres' => 'Medico de Guardia HCEApp',
                'especialidad_id' => $especialidad->id,
                'tipo_colegiatura' => 'CMP',
                'rne' => '00001',
                'estado' => 'Activo',
                'codigo_servicio' => 'EMG-001',
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ],
        );

        $medico = User::query()->updateOrCreate(
            ['email' => 'medico.seed@hceapp.test'],
            [
                'name' => 'Medico de Guardia HCEApp',
                'tipo_usuario' => 'Profesional de Salud',
                'profesional_salud_id' => $profesional->id,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'activo' => true,
                'registrado_por' => $administrador->id,
                'actualizado_por' => $administrador->id,
            ],
        );

        $medico->assignRole(Role::findOrCreate('Medico', 'web'));
    }
}
