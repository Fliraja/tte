<?php

namespace Database\Factories;

use App\Models\Signer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignerFactory extends Factory
{
    protected $model = Signer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'is_active' => true,
        ];
    }
}
