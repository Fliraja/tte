<?php

namespace Database\Factories;

use App\Models\QrGeneration;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QrGenerationFactory extends Factory
{
    protected $model = QrGeneration::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'signer_id' => Signer::factory(),
            'letter_number' => null,
            'generated_by' => User::factory(),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
