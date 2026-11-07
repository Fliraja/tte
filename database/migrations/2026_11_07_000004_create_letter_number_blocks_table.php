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
        Schema::create('letter_number_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('start_number');
            $table->unsignedInteger('end_number')->nullable();
            $table->unsignedInteger('next_number');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['year', 'start_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_number_blocks');
    }
};

