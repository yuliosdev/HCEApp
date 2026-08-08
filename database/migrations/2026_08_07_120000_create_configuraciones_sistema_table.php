<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la configuracion visual inicial del sistema.
     */
    public function up(): void
    {
        Schema::create('configuraciones_sistema', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_sistema')->default('HCEApp');
            $table->string('titulo_login')->default('Acceso seguro a HCEApp');
            $table->string('subtitulo_login')->default('Historia clinica electronica de emergencias');
            $table->string('texto_institucional_login')->nullable();
            $table->string('logo_path')->default('/images/login/logo-default.png');
            $table->string('login_background_path')->default('/images/login/login-background-default.png');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Elimina la configuracion visual inicial del sistema.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_sistema');
    }
};
