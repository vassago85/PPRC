<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('match_director_name', 150)->nullable()->after('match_director_id');
        });

        // Copy linked user names into the free-text field so existing rows keep their MD label.
        if (Schema::hasTable('users')) {
            foreach (DB::table('events')->whereNotNull('match_director_id')->cursor() as $event) {
                if (filled($event->match_director_name ?? null)) {
                    continue;
                }
                $name = DB::table('users')->where('id', $event->match_director_id)->value('name');
                if ($name) {
                    DB::table('events')->where('id', $event->id)->update(['match_director_name' => $name]);
                }
            }
        }

        // Public site requires published_at; older rows may have status set without it.
        DB::table('events')
            ->whereIn('status', ['published', 'completed'])
            ->whereNull('published_at')
            ->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('match_director_name');
        });
    }
};
