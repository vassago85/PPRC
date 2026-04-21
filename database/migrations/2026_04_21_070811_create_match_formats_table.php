<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match formats: "PRS (Centerfire)", "PR22", training matches, etc. Kept as
 * its own table (not a column enum) so new disciplines can be added by a
 * chairperson via Filament without code changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_formats', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_formats');
    }
};
