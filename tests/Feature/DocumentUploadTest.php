<?php

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\QrGeneration;
use App\Models\QrGenerationEvent;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private User $otherStaff;
    private User $admin;
    private Signer $signer;
    private LetterType $letterType;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Config::set('tte.documents_enabled', true);
        Config::set('tte.document_disk', 'local');
        Config::set('tte.document_max_kb', 5120);

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->otherStaff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->signer = Signer::factory()->create(['is_active' => true]);
        $this->letterType = LetterType::factory()->create([
            'name' => 'Surat Keputusan',
            'code' => 'SK',
            'show_content' => true,
            'expects_document' => true,
        ]);
    }

    private function createFakePdf(string $name = 'test.pdf', int $kilobytes = 10): UploadedFile
    {
        $content = "%PDF-1.4\n" . str_repeat('A', max(1, $kilobytes * 1024 - 10));
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    public function test_staff_can_see_upload_box_and_submit_button_on_issued_letter()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'issued',
        ]);

        $response = $this->actingAs($this->staff)->get("/qr/{$generation->uuid}");

        $response->assertOk();
        $response->assertSee('Unggah Dokumen Final (PDF)');
        $response->assertSee('Unggah Dokumen &amp; Kunci Final', false);
        $response->assertSee('name="document"', false);
    }

    public function test_staff_can_upload_valid_pdf_on_their_own_issued_letter()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'status' => 'issued',
            'number_year' => 2026,
        ]);

        $pdf = $this->createFakePdf('final-doc.pdf', 50);

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $pdf,
            ]);

        $response->assertRedirect("/qr/{$generation->uuid}");
        $response->assertSessionHas('success');

        $generation->refresh();
        $this->assertEquals('final', $generation->status);
        $this->assertNotNull($generation->document_path);
        $this->assertNotNull($generation->document_hash);
        $this->assertEquals($this->staff->id, $generation->document_uploaded_by);

        Storage::disk('local')->assertExists($generation->document_path);

        $this->assertDatabaseHas('qr_generation_events', [
            'qr_generation_id' => $generation->id,
            'user_id' => $this->staff->id,
            'event' => 'document_uploaded',
        ]);
    }

    public function test_upload_rejects_non_pdf_or_invalid_magic_bytes()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'issued',
        ]);

        // Fake file with invalid header (not %PDF-)
        $fakePdf = UploadedFile::fake()->createWithContent('fake.pdf', 'NOT_A_REAL_PDF_CONTENT');

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $fakePdf,
            ]);

        $response->assertSessionHasErrors('document');
        $generation->refresh();
        $this->assertEquals('issued', $generation->status);
    }

    public function test_upload_rejects_file_exceeding_max_kb()
    {
        Config::set('tte.document_max_kb', 10); // 10 KB limit

        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'issued',
        ]);

        $largePdf = $this->createFakePdf('large.pdf', 25); // 25 KB > 10 KB

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $largePdf,
            ]);

        $response->assertSessionHasErrors('document');
    }

    public function test_staff_cannot_upload_on_another_users_letter()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'issued',
        ]);

        $pdf = $this->createFakePdf();

        $response = $this->actingAs($this->otherStaff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $pdf,
            ]);

        $response->assertForbidden();
    }

    public function test_upload_rejected_if_letter_already_final()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'final',
            'document_path' => 'letters/2026/sample.pdf',
        ]);

        $pdf = $this->createFakePdf();

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $pdf,
            ]);

        $response->assertForbidden();
    }

    public function test_upload_rejected_if_letter_is_void()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'void',
            'void_reason' => 'Dibatalkan',
        ]);

        $pdf = $this->createFakePdf();

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document", [
                'document' => $pdf,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_replace_document_on_final_letter_with_reason()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'final',
            'document_path' => 'letters/2026/initial.pdf',
            'document_hash' => 'old-hash-1234567890',
        ]);

        $newPdf = $this->createFakePdf('replacement.pdf', 30);

        $response = $this->actingAs($this->admin)
            ->post("/qr/{$generation->uuid}/document/replace", [
                'document' => $newPdf,
                'reason' => 'Koreksi nomor stempel lampiran surat',
            ]);

        $response->assertRedirect("/qr/{$generation->uuid}");
        $response->assertSessionHas('success');

        $generation->refresh();
        $this->assertNotEquals('old-hash-1234567890', $generation->document_hash);

        $this->assertDatabaseHas('qr_generation_events', [
            'qr_generation_id' => $generation->id,
            'user_id' => $this->admin->id,
            'event' => 'document_replaced',
        ]);
    }

    public function test_staff_cannot_replace_document()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'final',
            'document_path' => 'letters/2026/initial.pdf',
        ]);

        $newPdf = $this->createFakePdf();

        $response = $this->actingAs($this->staff)
            ->post("/qr/{$generation->uuid}/document/replace", [
                'document' => $newPdf,
                'reason' => 'Ingin mengganti',
            ]);

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_stream_document()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'status' => 'final',
            'document_path' => 'letters/2026/doc.pdf',
        ]);

        Storage::disk('local')->put('letters/2026/doc.pdf', "%PDF-1.4 Sample");

        $response = $this->actingAs($this->staff)
            ->get("/qr/{$generation->uuid}/document");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_public_verify_stream_serves_pdf_for_final_letter_with_security_headers()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'status' => 'final',
            'document_path' => 'letters/2026/doc.pdf',
        ]);

        Storage::disk('local')->put('letters/2026/doc.pdf', "%PDF-1.4 Public Content");

        $response = $this->get("/verify/{$generation->uuid}/document");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_public_verify_stream_returns_404_if_status_is_not_final()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'status' => 'issued',
            'document_path' => null,
        ]);

        $this->get("/verify/{$generation->uuid}/document")->assertNotFound();
    }

    public function test_public_verify_stream_returns_404_if_void()
    {
        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'letter_type_id' => $this->letterType->id,
            'status' => 'void',
            'document_path' => 'letters/2026/doc.pdf',
        ]);

        Storage::disk('local')->put('letters/2026/doc.pdf', "%PDF-1.4 Public Content");

        $this->get("/verify/{$generation->uuid}/document")->assertNotFound();
    }

    public function test_public_verify_stream_returns_404_if_show_content_is_false()
    {
        $privateType = LetterType::factory()->create([
            'name' => 'Hasil MCU',
            'show_content' => false,
        ]);

        $generation = QrGeneration::factory()->create([
            'generated_by' => $this->staff->id,
            'signer_id' => $this->signer->id,
            'letter_type_id' => $privateType->id,
            'status' => 'final',
            'document_path' => 'letters/2026/doc.pdf',
        ]);

        Storage::disk('local')->put('letters/2026/doc.pdf', "%PDF-1.4 Secret Medical Data");

        $this->get("/verify/{$generation->uuid}/document")->assertNotFound();
    }
}

