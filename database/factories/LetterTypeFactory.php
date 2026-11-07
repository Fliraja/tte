<?php

namespace Database\Factories;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterTypeFactory extends Factory
{
    protected $model = LetterType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('##'),
            'name' => fake()->words(3, true),
            'is_active' => true,
            'numbering' => 'auto',
            'show_content' => true,
            'expects_document' => true,
            'reference_label' => null,
            'reference_required' => false,
            'uses_unit' => false,
            'fixed_peruntukan' => null,
        ];
    }
}
