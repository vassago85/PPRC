<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('memberships')
            ->where('status', 'active')
            ->whereNull('certificate_token')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->each(function ($membership) use ($now) {
                DB::table('memberships')
                    ->where('id', $membership->id)
                    ->update([
                        'certificate_token' => Str::lower(Str::random(40)),
                        'certificate_issued_at' => $now,
                    ]);
            });
    }

    public function down(): void
    {
        // Tokens are non-destructive; no rollback needed.
    }
};
