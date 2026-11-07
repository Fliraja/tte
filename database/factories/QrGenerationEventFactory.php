<?php

namespace Database\Factories;

use App\Models\QrGeneration;
use App\Models\QrGenerationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QrGenerationEventFactory extends Factory
{
    protected $model = QrGenerationEvent::class;

    public function definition(): array
    {
        return [
            'qr_generation_id' => QrGeneration::factory(),
            'user_id' => User::factory(),
            'event' => 'issued',
            'note' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}

