<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'signer_id',
        'letter_type_id',
        'destination_id',
        'unit_id',
        'letter_number',
        'perihal',
        'sequence_number',
        'number_year',
        'number_month',
        'number_source',
        'override_reason',
        'reference',
        'signer_name',
        'signer_position',
        'status',
        'document_path',
        'document_hash',
        'document_uploaded_at',
        'document_uploaded_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'generated_by',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'number_year' => 'integer',
            'document_uploaded_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(Signer::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(LetterDestination::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(LetterUnit::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function documentUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'document_uploaded_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(QrGenerationEvent::class);
    }

    public function isLegacy(): bool
    {
        return $this->status === 'legacy';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }

    public function isVoid(): bool
    {
        return $this->status === 'void';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}
