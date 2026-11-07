<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qr_generations', function (Blueprint $table) {
            $table->foreignId('letter_type_id')->nullable()->constrained('letter_types');
            $table->foreignId('destination_id')->nullable()->constrained('letter_destinations');
            $table->foreignId('unit_id')->nullable()->constrained('letter_units');

            $table->unsignedInteger('sequence_number')->nullable();
            $table->unsignedSmallInteger('number_year')->nullable();
            $table->string('number_month', 10)->nullable();
            $table->string('number_source', 20)->nullable();
            $table->text('override_reason')->nullable();

            $table->string('reference', 100)->nullable()->index();
            $table->string('signer_name')->nullable();
            $table->string('signer_position')->nullable();
            $table->string('status', 20)->default('issued');

            $table->string('document_path')->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->timestamp('document_uploaded_at')->nullable();
            $table->foreignId('document_uploaded_by')->nullable()->constrained('users');

            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->text('void_reason')->nullable();

            $table->unique(['number_year', 'sequence_number']);
        });

        // Backfill legacy rows: set status='legacy' and snapshot signer
        $legacyRows = DB::table('qr_generations')
            ->leftJoin('signers', 'qr_generations.signer_id', '=', 'signers.id')
            ->select('qr_generations.id', 'signers.name as s_name', 'signers.position as s_pos')
            ->get();

        foreach ($legacyRows as $row) {
            DB::table('qr_generations')
                ->where('id', $row->id)
                ->update([
                    'status' => 'legacy',
                    'signer_name' => $row->s_name,
                    'signer_position' => $row->s_pos,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qr_generations', function (Blueprint $table) {
            $table->dropUnique(['number_year', 'sequence_number']);
            $table->dropIndex(['reference']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['letter_type_id']);
                $table->dropForeign(['destination_id']);
                $table->dropForeign(['unit_id']);
                $table->dropForeign(['document_uploaded_by']);
                $table->dropForeign(['voided_by']);
            }

            $table->dropColumn([
                'letter_type_id',
                'destination_id',
                'unit_id',
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
            ]);
        });
    }
};

