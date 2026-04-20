<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership types mirror the legacy SSMM PPRC plugin rules so existing
 * committee processes (manual approval for new members, junior age caps,
 * spouse sub-member linkage, annual pricing changes) transfer cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedSmallInteger('duration_months')->default(12);

            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_registration')->default(true);
            $table->boolean('requires_manual_approval')->default(true);
            $table->boolean('assign_membership_number_on_approval')->default(true);
            $table->boolean('counts_as_member')->default(true);

            $table->boolean('allows_sub_members')->default(false);
            $table->jsonb('allowed_sub_member_type_slugs')->nullable();

            $table->boolean('has_age_requirement')->default(false);
            $table->string('age_requirement_type')->nullable();
            $table->unsignedSmallInteger('age_min')->nullable();
            $table->unsignedSmallInteger('age_max')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_types');
    }
};
