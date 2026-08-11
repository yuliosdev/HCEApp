<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega campos operativos de seguridad a los usuarios de HCEApp.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('activo')->default(true)->after('password');
            $table->foreignId('registrado_por')
                ->nullable()
                ->after('remember_token')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->after('registrado_por')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Revierte los campos agregados para seguridad de usuarios.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('actualizado_por');
            $table->dropConstrainedForeignId('registrado_por');
            $table->dropColumn('activo');
        });
    }
};
