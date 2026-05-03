<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Renames event_results.class -> event_results.category.
 *
 * "Class" was the PractiScore export header (rifle/equipment class), but PPRC
 * uses it to mean shooter category (Ladies, Seniors, Juniors, ...). Renaming
 * makes filters and admin labels read correctly, and lets us add a real
 * (event_id, category, rank) index for the public results filter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->renameColumn('class', 'category');
        });

        Schema::table('event_results', function (Blueprint $table) {
            $table->index(['event_id', 'category', 'rank'], 'event_results_event_id_category_rank_index');
        });
    }

    public function down(): void
    {
        Schema::table('event_results', function (Blueprint $table) {
            $table->dropIndex('event_results_event_id_category_rank_index');
        });

        Schema::table('event_results', function (Blueprint $table) {
            $table->renameColumn('category', 'class');
        });
    }
};
