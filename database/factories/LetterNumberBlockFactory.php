<?php

namespace Database\Factories;

use App\Models\LetterNumberBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterNumberBlockFactory extends Factory
{
    protected $model = LetterNumberBlock::class;

    public function definition(): array
    {
        $start = fake()->numberBetween(1, 1000);
        $end = $start + fake()->numberBetween(10, 100);

        return [
            'year' => (int) date('Y'),
            'start_number' => $start,
            'end_number' => $end,
            'next_number' => $start,
            'created_by' => User::factory(),
        ];
    }
}
