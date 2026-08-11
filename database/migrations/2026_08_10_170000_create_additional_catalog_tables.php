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
        Schema::create('ambientes', function (Blueprint $table): void {
            $table->id();
            $table->string('descripcion', 150)->unique();
            $table->string('ubicacion', 150)->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $this->auditoriaBasica($table);
        });
    }

    /**
     * Elimina los catalogos adicionales.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambientes');
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
