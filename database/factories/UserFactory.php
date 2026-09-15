<?php

namespace Database\Factories;

use App\Enums\AdminRole;
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
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Bawaan Admin Utama: tes lama ditulis saat semua akun berakses
            // penuh. Tes untuk akun staf memakai ->pengurus().
            'role' => AdminRole::AdminUtama,
            'is_active' => true,
        ];
    }

    /** Akun staf biasa (bukan Admin Utama). */
    public function pengurus(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => AdminRole::Pengurus,
        ]);
    }

    /** Akun yang sudah dinonaktifkan. */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
