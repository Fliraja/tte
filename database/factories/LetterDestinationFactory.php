<?php

namespace Database\Factories;

use App\Models\LetterDestination;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterDestinationFactory extends Factory
{
    protected $model = LetterDestination::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'is_active' => true,
        ];
    }
}
