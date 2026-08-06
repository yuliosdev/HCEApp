<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioAdministradorSeeder extends Seeder
{
    /**
     * Crea el usuario administrador inicial para el entorno local.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@hceapp.test'],
            [
                'name' => 'Administrador HCEApp',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
