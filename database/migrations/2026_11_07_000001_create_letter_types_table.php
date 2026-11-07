<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('numbering', 20)->default('auto');
            $table->boolean('show_content')->default(true);
            $table->boolean('expects_document')->default(true);
            $table->string('reference_label')->nullable();
            $table->boolean('reference_required')->default(false);
            $table->boolean('uses_unit')->default(false);
            $table->string('fixed_peruntukan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_types');
    }
};
