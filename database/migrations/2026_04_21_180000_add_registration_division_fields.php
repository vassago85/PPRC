<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('registration_division_options')->nullable()->after('club_round_count');
            $table->json('registration_category_options')->nullable()->after('registration_division_options');
            $table->boolean('registration_require_division')->default(true)->after('registration_category_options');
            $table->boolean('registration_require_category')->default(false)->after('registration_require_division');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('division', 80)->nullable()->after('guest_phone')->index();
            $table->string('category', 80)->nullable()->after('division')->index();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'registration_division_options',
                'registration_category_options',
                'registration_require_division',
                'registration_require_category',
            ]);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['division', 'category']);
        });
    }
};
