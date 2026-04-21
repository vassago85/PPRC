<?php

use App\Enums\MembershipStatus;
use App\Models\Membership;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('certificate_token', 64)->nullable()->unique()->after('admin_notes');
            $table->timestamp('certificate_issued_at')->nullable()->after('certificate_token');
        });

        Membership::query()
            ->where('status', MembershipStatus::Active)
            ->whereNull('certificate_token')
            ->each(function (Membership $membership): void {
                $membership->forceFill([
                    'certificate_token' => Str::lower(Str::random(40)),
                    'certificate_issued_at' => $membership->updated_at ?? now(),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['certificate_token', 'certificate_issued_at']);
        });
    }
};
