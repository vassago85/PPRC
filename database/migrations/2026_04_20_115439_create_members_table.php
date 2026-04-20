<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `members` is a 1:1 profile extension for `users` that matches the SSMM
 * plugin's meta shape. Authentication + email stays on `users`; everything
 * else (club number, address, DoB, disciplines, SAPRF, junior->adult link)
 * lives here so we don't pollute the auth table and can migrate safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('known_as')->nullable();

            $table->string('membership_number')->nullable()->unique();

            $table->string('phone_country_code', 8)->nullable();
            $table->string('phone_number', 32)->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('country', 64)->default('South Africa');

            $table->date('date_of_birth')->nullable();

            $table->jsonb('shooting_disciplines')->nullable();

            $table->string('profile_photo_path')->nullable();

            $table->string('status')->default('pending')->index();
            $table->date('join_date')->nullable();
            $table->date('expiry_date')->nullable()->index();

            $table->foreignId('linked_adult_member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->string('saprf_membership_number')->nullable()->index();
            $table->timestamp('saprf_verified_at')->nullable();
            $table->text('saprf_notes')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
