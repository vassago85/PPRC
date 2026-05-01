<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match books are the printed PDF stage descriptions / shooter packs that
 * the match director hands out before the match. Letting MDs upload them
 * once and embed them on the public match page means shooters can study
 * the stages from their phones instead of waiting for printed copies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('match_book_path', 500)->nullable()->after('banner_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('match_book_path');
        });
    }
};
