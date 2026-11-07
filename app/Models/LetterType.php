<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'numbering',
        'show_content',
        'expects_document',
        'reference_label',
        'reference_required',
        'uses_unit',
        'fixed_peruntukan',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_content' => 'boolean',
            'expects_document' => 'boolean',
            'reference_required' => 'boolean',
            'uses_unit' => 'boolean',
        ];
    }

    public function generations(): HasMany
    {
        return $this->hasMany(QrGeneration::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

