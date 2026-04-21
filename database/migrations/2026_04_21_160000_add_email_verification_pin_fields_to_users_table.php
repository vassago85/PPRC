<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('created_via_import')->default(false);
            $table->string('email_verification_pin_hash')->nullable();
            $table->timestamp('email_verification_pin_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'created_via_import',
                'email_verification_pin_hash',
                'email_verification_pin_expires_at',
            ]);
        });
    }
};
