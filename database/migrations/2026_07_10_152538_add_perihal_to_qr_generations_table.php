<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_generations', function (Blueprint $table) {
            $table->string('perihal')->nullable()->after('letter_number');
        });
    }

    public function down(): void
    {
        Schema::table('qr_generations', function (Blueprint $table) {
            $table->dropColumn('perihal');
        });
    }
};
