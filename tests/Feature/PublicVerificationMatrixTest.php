<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\QrGeneration;
use App\Models\QrGenerationEvent;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVerificationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Signer $signer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_active' => true]);
        $this->signer = Signer::factory()->create([
            'name' => 'dr. Direktur Utama',
            'position' => 'Direktur',
            'is_active' => true,
        ]);
    }

    public function test_verify_returns_required_security_headers()
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'signer_name' => $this->signer->name,
            'signer_position' => $this->signer->position,
            'generated_by' => $this->user->id,
            'status' => 'issued',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk();
        $res->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $res->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', (string) $res->headers->get('Cache-Control'));
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_verify_not_found_also_returns_security_headers()
    {
        $res = $this->get('/verify/non-existent-uuid');

        $res->assertOk();
        $res->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $res->assertHeader('Referrer-Policy', 'no-referrer');
        $res->assertSee('Kode Tidak Ditemukan');
    }

    public function test_verify_standard_issued_letter_matrix()
    {
        $type = LetterType::factory()->create([
            'name' => 'Surat Keputusan',
            'code' => 'SK',
            'numbering' => 'auto',
            'expects_document' => true,
        ]);

        $gen = QrGeneration::factory()->create([
            'letter_type_id' => $type->id,
            'signer_id' => $this->signer->id,
            'signer_name' => 'dr. Direktur Utama',
            'signer_position' => 'Direktur',
            'generated_by' => $this->user->id,
            'letter_number' => '1001/RSUN/SK/VII/2026',
            'perihal' => 'Pengangkatan Pegawai Baru',
            'status' => 'issued',
            'number_source' => 'auto',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk()
            ->assertSee('Verifikasi Dokumen Internal')
            ->assertSee('dr. Direktur Utama')
            ->assertSee('Direktur')
            ->assertSee('1001/RSUN/SK/VII/2026')
            ->assertSee('Surat Keputusan')
            ->assertSee('Pengangkatan Pegawai Baru')
            ->assertSee('WITA')
            ->assertSee('Verifikasi internal RSU Nirwana. Bukan tanda tangan elektronik tersertifikasi. Cocokkan isi dengan dokumen fisik.');
    }

    public function test_verify_none_numbering_mcu_variant()
    {
        $type = LetterType::factory()->create([
            'name' => 'Hasil MCU',
            'code' => 'MCU',
            'numbering' => 'none',
            'reference_label' => 'Nomor MCU',
            'expects_document' => false,
        ]);

        $gen = QrGeneration::factory()->create([
            'letter_type_id' => $type->id,
            'signer_id' => $this->signer->id,
            'signer_name' => 'dr. Penanggung Jawab MCU',
            'signer_position' => 'Dokter Pemeriksa',
            'generated_by' => $this->user->id,
            'letter_number' => null,
            'reference' => 'MCU-2026-0089',
            'perihal' => 'Pemeriksaan Kesehatan Calon Karyawan PT ABC',
            'status' => 'issued',
            'number_source' => 'none',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk()
            ->assertDontSee('Nomor Surat:')
            ->assertSee('Nomor MCU:')
            ->assertSee('MCU-2026-0089')
            ->assertSee('QR ini hanya mencatat bahwa dokumen diterbitkan oleh penandatangan di atas pada waktu tertentu. Isi dan keaslian dokumen tidak dapat diverifikasi dari halaman ini.')
            ->assertDontSee('Pemberitahuan: Isi naskah fisik surat final belum diunggah ke sistem.');
    }

    public function test_verify_void_letter_shows_void_banner_and_reason()
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'signer_name' => 'dr. Direktur Utama',
            'signer_position' => 'Direktur',
            'generated_by' => $this->user->id,
            'letter_number' => '1005/RSUN/SK/VII/2026',
            'perihal' => 'SK Direktur Keliru',
            'status' => 'void',
            'voided_by' => $this->user->id,
            'voided_at' => now(),
            'void_reason' => 'Salah ketik perihal dan lampiran tidak lengkap',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk()
            ->assertSee('Surat Dibatalkan (Void)')
            ->assertSee('Dokumen ini telah dinyatakan tidak berlaku lagi oleh manajemen RSU Nirwana.')
            ->assertSee('1005/RSUN/SK/VII/2026')
            ->assertSee('SK Direktur Keliru')
            ->assertSee('Salah ketik perihal dan lampiran tidak lengkap')
            ->assertSee('WITA');
    }

    public function test_verify_legacy_letter_renders_gracefully_with_fallback()
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'signer_name' => null,
            'signer_position' => null,
            'generated_by' => $this->user->id,
            'letter_number' => '050/DIR/VI/2025',
            'perihal' => 'Surat Pengantar Lama',
            'status' => 'legacy',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk()
            ->assertSee('dr. Direktur Utama')
            ->assertSee('Direktur')
            ->assertSee('050/DIR/VI/2025')
            ->assertSee('WITA');
    }

    public function test_verify_preserves_snapshot_integrity_when_master_signer_is_modified()
    {
        $gen = QrGeneration::factory()->create([
            'signer_id' => $this->signer->id,
            'signer_name' => 'dr. Direktur Snapshot',
            'signer_position' => 'Direktur Lama',
            'generated_by' => $this->user->id,
            'letter_number' => '1008/RSUN/SK/VII/2026',
            'status' => 'issued',
        ]);

        // Change signer master data
        $this->signer->update([
            'name' => 'dr. Pejabat Baru',
            'position' => 'Pelaksana Tugas',
        ]);

        $res = $this->get('/verify/' . $gen->uuid);

        $res->assertOk()
            ->assertSee('dr. Direktur Snapshot')
            ->assertSee('Direktur Lama')
            ->assertDontSee('dr. Pejabat Baru')
            ->assertDontSee('Pelaksana Tugas');
    }
}
