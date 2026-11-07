<?php

namespace Database\Factories;

use App\Models\LetterUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterUnitFactory extends Factory
{
    protected $model = LetterUnit::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}

