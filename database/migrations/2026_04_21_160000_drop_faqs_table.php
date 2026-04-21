<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('faqs');
    }

    public function down(): void
    {
        // Intentionally empty — FAQs are static Blade; the table is not recreated.
    }
};
