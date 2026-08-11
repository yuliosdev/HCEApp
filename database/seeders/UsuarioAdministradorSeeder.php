<?php

namespace Database\Seeders;

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
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'activo' => true,
            ],
        );

        $administrador->assignRole($rolAdministrador);

        $julio = User::query()->updateOrCreate(
            ['email' => 'yuliostch@gmail.com'],
            [
                'name' => 'Julio Tavara',
                'email_verified_at' => now(),
                'password' => Hash::make('Inicio123'),
                'activo' => true,
            ],
        );

        $julio->assignRole($rolAdministrador);
    }
}
