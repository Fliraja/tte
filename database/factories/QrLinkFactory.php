<?php

namespace Database\Factories;

use App\Models\QrLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QrLinkFactory extends Factory
{
    protected $model = QrLink::class;

    public function definition(): array
    {
        return [
            // Alfabet sama dengan QrLinkService (tanpa i, l, o, 0, 1)
            'code' => fake()->unique()->regexify('[a-hjkmnp-z2-9]{10}'),
            'target_url' => 'https://drive.google.com/file/d/' . Str::random(28) . '/view?usp=sharing',
            'label' => fake()->sentence(3),
            'status' => QrLink::STATUS_ACTIVE,
            'scan_count' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function disabled(string $reason = 'Salah sebar'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => QrLink::STATUS_DISABLED,
            'disabled_at' => now(),
            'disabled_by' => User::factory(),
            'disable_reason' => $reason,
        ]);
    }
}
