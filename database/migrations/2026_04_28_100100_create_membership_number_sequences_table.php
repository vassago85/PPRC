<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row counter for membership number allocation. Using a dedicated
 * table with lockForUpdate() gives hard guarantees under concurrent PHP
 * workers — stronger than the previous cache lock approach.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique('prefix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_number_sequences');
    }
};
