<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exco_members', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('position', 120);
            $table->text('bio')->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('term_started_on')->nullable();
            $table->date('term_ends_on')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_current', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exco_members');
    }
};
