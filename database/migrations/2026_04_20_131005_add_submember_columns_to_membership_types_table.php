<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->boolean('is_sub_membership')->default(false)->after('counts_as_member');
            $table->boolean('free_while_linked_adult_active')->default(false)->after('is_sub_membership');
            $table->unsignedSmallInteger('max_per_parent')->nullable()->after('free_while_linked_adult_active');
        });
    }

    public function down(): void
    {
        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropColumn([
                'is_sub_membership',
                'free_while_linked_adult_active',
                'max_per_parent',
            ]);
        });
    }
};
