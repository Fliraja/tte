<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterNumberBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'start_number',
        'end_number',
        'next_number',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_number' => 'integer',
            'end_number' => 'integer',
            'next_number' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function remainingNumbers(): ?int
    {
        if ($this->end_number === null) {
            return null; // Unlimited block
        }

        return max(0, $this->end_number - $this->next_number + 1);
    }

    public function isExhausted(): bool
    {
        return $this->end_number !== null && $this->next_number > $this->end_number;
    }
}
