<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'description' => fake()->sentence(),
            'is_system' => false,
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn () => [
            'name' => 'FAST Administrator',
            'slug' => Role::SLUG_FAST_ADMINISTRATOR,
            'is_system' => true,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn () => [
            'name' => 'Supplier User',
            'slug' => Role::SLUG_SUPPLIER_USER,
            'is_system' => true,
        ]);
    }
}
