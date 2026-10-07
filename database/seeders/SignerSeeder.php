<?php

namespace Database\Seeders;

use App\Models\Signer;
use Illuminate\Database\Seeder;

class SignerSeeder extends Seeder
{
    public function run(): void
    {
        Signer::insert([
            ['name' => 'dr. Budi Santoso', 'position' => 'Direktur Utama', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'dr. Siti Aminah', 'position' => 'Wakil Direktur', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
