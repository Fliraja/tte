<?php

namespace Tests\Feature;

use App\Models\LetterDestination;
use App\Models\LetterType;
use App\Models\LetterUnit;
use Database\Seeders\LetterMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterMasterSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_letter_master_seeder_populates_master_tables_idempotently()
    {
        $this->seed(LetterMasterSeeder::class);

        $this->assertDatabaseHas('letter_types', ['code' => '01', 'name' => 'Surat Keputusan (SK)']);
        $this->assertDatabaseHas('letter_types', ['code' => '16', 'uses_unit' => true, 'fixed_peruntukan' => 'SPO']);
        $this->assertDatabaseHas('letter_types', ['code' => 'SDM', 'numbering' => 'manual']);
        $this->assertDatabaseHas('letter_types', ['code' => 'MCU', 'numbering' => 'none', 'expects_document' => false]);

        $this->assertDatabaseHas('letter_destinations', ['code' => 'DIR']);
        $this->assertDatabaseHas('letter_destinations', ['code' => 'SPO']);

        $this->assertDatabaseHas('letter_units', ['code' => 'IBS']);
        $this->assertDatabaseHas('letter_units', ['code' => 'IGD']);

        // Run second time to verify idempotency
        $this->seed(LetterMasterSeeder::class);

        $this->assertEquals(14, LetterType::count());
        $this->assertEquals(8, LetterDestination::count());
        $this->assertEquals(11, LetterUnit::count());
    }
}

