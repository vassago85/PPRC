<?php

use App\Models\ExcoMember;
use Illuminate\Database\Migrations\Migration;

/**
 * Until now, the ExCo roster (used on the public /about page) was a separate
 * data store from the Spatie roles that gate admin/portal access. Assigning
 * "Vice Chair" to a person on the roster did NOT grant `vice_chair` admin
 * permissions — they had to be set in two places.
 *
 * The ExcoMember model now auto-syncs the linked user's role on save. This
 * one-off pass walks every current ExCo seat with a linked user and ensures
 * the matching role is granted, so the existing committee gets access
 * without anyone needing to re-save the form.
 */
return new class extends Migration
{
    public function up(): void
    {
        ExcoMember::query()
            ->where('is_current', true)
            ->whereNotNull('linked_user_id')
            ->get()
            ->each(fn (ExcoMember $exco) => $exco->syncLinkedUserRole());
    }

    public function down(): void
    {
        // No-op: this is an additive role grant. Revoke via user-roles admin.
    }
};
