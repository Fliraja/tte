<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrLinkEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'qr_link_id',
        'user_id',
        'event',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function qrLink(): BelongsTo
    {
        return $this->belongsTo(QrLink::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
