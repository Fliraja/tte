<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'position', 'is_active'];

    public function generations()
    {
        return $this->hasMany(QrGeneration::class);
    }
}
