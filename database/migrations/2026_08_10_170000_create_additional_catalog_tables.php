<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea catalogos adicionales requeridos por el menu de mantenimiento.
     */
    public function up(): void
    {
        //
    }

    /**
     * Elimina los catalogos adicionales.
     */
    public function down(): void
    {
        //
    }

    /**
     * Agrega auditoria basica relacionada con users y eliminacion logica.
     */
    private function auditoriaBasica(Blueprint $table): void
    {
        $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();
    }
};
