<?php

namespace Database\Seeders;

use App\Models\LetterDestination;
use App\Models\LetterType;
use App\Models\LetterUnit;
use Illuminate\Database\Seeder;

class LetterMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Jenis Surat (Letter Types)
        $types = [
            [
                'code' => '01',
                'name' => 'Surat Keputusan (SK)',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '02',
                'name' => 'Surat Undangan',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '03',
                'name' => 'Surat Edaran',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '04',
                'name' => 'Surat Tugas',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '05',
                'name' => 'Surat Keterangan',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '06',
                'name' => 'Surat Kuasa',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '07',
                'name' => 'Surat Pengantar',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '08',
                'name' => 'Surat Pemberitahuan',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '08.2',
                'name' => 'Surat Pemberitahuan Medis Pasien',
                'numbering' => 'auto',
                'show_content' => false, // K6: Data medis sensitif
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '10',
                'name' => 'Surat Pernyataan / Kesanggupan',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '14',
                'name' => 'Surat Keterangan Medis Khusus',
                'numbering' => 'auto',
                'show_content' => false, // K6: Data medis sensitif
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => '16',
                'name' => 'Standar Prosedur Operasional (SPO)',
                'numbering' => 'auto',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => true,
                'fixed_peruntukan' => 'SPO',
            ],
            [
                'code' => 'SDM',
                'name' => 'Surat SDM / Kepegawaian',
                'numbering' => 'manual',
                'show_content' => true,
                'expects_document' => true,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
            [
                'code' => 'MCU',
                'name' => 'Hasil MCU',
                'numbering' => 'none',
                'show_content' => false,
                'expects_document' => false,
                'reference_label' => 'Nomor Pemeriksaan',
                'reference_required' => false,
                'uses_unit' => false,
                'fixed_peruntukan' => null,
            ],
        ];

        foreach ($types as $type) {
            LetterType::updateOrCreate(
                ['code' => $type['code']],
                $type + ['is_active' => true]
            );
        }

        // 2. Kode Peruntukan (Letter Destinations)
        $destinations = [
            ['code' => 'DIR', 'name' => 'Direksi / Direktur'],
            ['code' => 'PEL', 'name' => 'Pelayanan Medis'],
            ['code' => 'KEP', 'name' => 'Keperawatan'],
            ['code' => 'PEN', 'name' => 'Penunjang Medis'],
            ['code' => 'UMM', 'name' => 'Umum & Operasional'],
            ['code' => 'KEU', 'name' => 'Keuangan & Akuntansi'],
            ['code' => 'SDM', 'name' => 'Sumber Daya Manusia'],
            ['code' => 'SPO', 'name' => 'Standar Prosedur Operasional'],
        ];

        foreach ($destinations as $dest) {
            LetterDestination::updateOrCreate(
                ['code' => $dest['code']],
                $dest + ['is_active' => true]
            );
        }

        // 3. Unit Surat untuk SPO (Letter Units)
        $units = [
            ['code' => 'IBS', 'name' => 'Instalasi Bedah Sentral'],
            ['code' => 'IGD', 'name' => 'Instalasi Gawat Darurat'],
            ['code' => 'RAWAT-INAP', 'name' => 'Rawat Inap'],
            ['code' => 'RAWAT-JALAN', 'name' => 'Rawat Jalan'],
            ['code' => 'LAB', 'name' => 'Laboratorium'],
            ['code' => 'RAD', 'name' => 'Radiologi'],
            ['code' => 'FARMASI', 'name' => 'Farmasi'],
            ['code' => 'GIZI', 'name' => 'Instalasi Gizi'],
            ['code' => 'REKAM-MEDIS', 'name' => 'Rekam Medis'],
            ['code' => 'VK', 'name' => 'Kamar Bersalin (VK)'],
            ['code' => 'ICU', 'name' => 'Intensive Care Unit (ICU)'],
        ];

        foreach ($units as $unit) {
            LetterUnit::updateOrCreate(
                ['code' => $unit['code']],
                $unit + ['is_active' => true]
            );
        }
    }
}

