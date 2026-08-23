<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Contrasena reutilizada para evitar recalcular hash en cada usuario ficticio.
     */
    protected static ?string $password;

    /**
     * Define el estado predeterminado del usuario ficticio.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'tipo_usuario' => fake()->randomElement(['Supervisor', 'Administrativo', 'Médico', 'Profesional de Salud']),
            'profesional_salud_id' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'activo' => true,
            'remember_token' => Str::random(10),
            'registrado_por' => null,
            'actualizado_por' => null,
        ];
    }

    /**
     * Marca el correo del usuario ficticio como no verificado.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
