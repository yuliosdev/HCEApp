<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecuta los seeders base de HCEApp.
     */
    public function run(): void
    {
        $this->call([
            UsuarioAdministradorSeeder::class,
            ConfiguracionSistemaSeeder::class,
            CatalogosClinicosSeeder::class,
        ]);
    }
}
