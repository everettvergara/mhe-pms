<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password1!'),
            'role_id' => Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id')
                ?? Role::factory(),
            'status' => UserStatus::Active,
            'is_super_admin' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->value('id')
                ?? Role::factory()->supplier(),
        ]);
    }
}
