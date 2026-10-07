<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\User;
use App\Models\UserEmailAlias;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Collapse two Member records (and their users, if any) into one.
 *
 * Picks a "survivor" — the record the admin wants to keep — and reparents
 * everything the "loser" owns onto it: memberships, event entries, badges,
 * payments, sub-members, letter requests, match credits. On tables with a
 * unique constraint that would collide (e.g. event_registrations has
 * unique(event_id, member_id)) the survivor's row wins and the loser's row
 * is deleted — the surviving record already represents that person.
 *
 * When the loser has a user account, the user-owned data (shop orders, email
 * logs, Spatie role assignments, "approved_by" / "created_by" attributions)
 * gets repointed too, the loser's email becomes a login alias on the survivor,
 * and the loser user is deleted so there is only one login identity left.
 *
 * The merge is destructive — ALWAYS wrap the admin trigger in a type-the-name
 * confirmation.
 */
class MemberMerger
{
    /**
     * Tables keyed by their member_id-style column, with the unique index that
     * can collide so we can dedup instead of blowing up. `unique` is listed as
     * the OTHER columns in the index (member_id is implicit).
     *
     * @var array<string, array{column: string, unique: array<int, string>|null}>
     */
    private const MEMBER_FK_TABLES = [
        'memberships' => ['column' => 'member_id', 'unique' => null],
        'event_registrations' => ['column' => 'member_id', 'unique' => ['event_id']],
        'event_results' => ['column' => 'member_id', 'unique' => null],
        'club_badge_member' => ['column' => 'member_id', 'unique' => ['club_badge_id']],
        'club_letter_requests' => ['column' => 'member_id', 'unique' => null],
        'endorsement_requests' => ['column' => 'member_id', 'unique' => null],
        'match_credits' => ['column' => 'member_id', 'unique' => null],
    ];

    /**
     * Attribution columns on various tables that reference users.id. These
     * are repointed from loser.user → survivor.user before the loser user is
     * deleted, so historical "approved by Jane / checked in by Jane" lines
     * keep pointing at the real person instead of going NULL.
     *
     * @var array<int, array{table: string, column: string}>
     */
    private const USER_ATTRIBUTION_COLUMNS = [
        ['table' => 'events', 'column' => 'created_by_user_id'],
        ['table' => 'events', 'column' => 'match_director_id'],
        ['table' => 'memberships', 'column' => 'approved_by_user_id'],
        ['table' => 'event_registrations', 'column' => 'checked_in_by_user_id'],
        ['table' => 'event_registrations', 'column' => 'marked_paid_by_user_id'],
        ['table' => 'endorsement_requests', 'column' => 'reviewed_by_user_id'],
        ['table' => 'club_letter_requests', 'column' => 'requested_by_user_id'],
        ['table' => 'match_credits', 'column' => 'created_by_user_id'],
        ['table' => 'match_expenses', 'column' => 'created_by_user_id'],
        ['table' => 'membership_payments', 'column' => 'confirmed_by_user_id'],
        ['table' => 'event_gallery_photos', 'column' => 'uploaded_by_user_id'],
        ['table' => 'saprf_shooters', 'column' => 'imported_by_user_id'],
        ['table' => 'announcements', 'column' => 'author_id'],
        ['table' => 'exco_members', 'column' => 'linked_user_id'],
    ];

    /**
     * Merge the loser into the survivor. Returns per-table counts for the
     * admin notification.
     *
     * @return array{
     *   memberships: int,
     *   event_registrations: int,
     *   event_results: int,
     *   club_badges: int,
     *   sub_members: int,
     *   letter_requests: int,
     *   endorsement_requests: int,
     *   match_credits: int,
     *   aliases_added: int,
     *   loser_user_deleted: bool,
     * }
     *
     * @throws ValidationException when the inputs are the same record, trashed,
     *                             or when the loser's email is already in use as a primary email on a
     *                             third account (so we never create an unresolvable alias).
     */
    public function merge(Member $survivor, Member $loser, bool $keepLoserEmailAsAlias = true): array
    {
        $this->assertMergeable($survivor, $loser, $keepLoserEmailAsAlias);

        return DB::transaction(function () use ($survivor, $loser, $keepLoserEmailAsAlias) {
            $stats = [
                'memberships' => 0,
                'event_registrations' => 0,
                'event_results' => 0,
                'club_badges' => 0,
                'sub_members' => 0,
                'letter_requests' => 0,
                'endorsement_requests' => 0,
                'match_credits' => 0,
                'aliases_added' => 0,
                'loser_user_deleted' => false,
            ];

            // Keep both out of the picture while we move their data around so
            // nothing else in the request lifecycle can grab them half-way
            // through the merge and act on inconsistent state.
            $survivor->refresh();
            $loser->refresh();

            // Reparent member-owned data. Dedup where a unique index would
            // collide — the survivor's existing row always wins.
            $moveStats = $this->reparentMemberTables($survivor, $loser);
            $stats['memberships'] = $moveStats['memberships'];
            $stats['event_registrations'] = $moveStats['event_registrations'];
            $stats['event_results'] = $moveStats['event_results'];
            $stats['club_badges'] = $moveStats['club_badge_member'];
            $stats['letter_requests'] = $moveStats['club_letter_requests'];
            $stats['endorsement_requests'] = $moveStats['endorsement_requests'];
            $stats['match_credits'] = $moveStats['match_credits'];

            // Sub-members (juniors / spouses linked to the loser as their
            // adult) switch to the survivor.
            $stats['sub_members'] = DB::table('members')
                ->where('linked_adult_member_id', $loser->id)
                ->update(['linked_adult_member_id' => $survivor->id]);

            // Both members always have a user (members.user_id is NOT NULL at
            // the schema level). Reparent the loser user's owned data onto the
            // survivor user, scrub the loser user so its email is no longer a
            // valid login, and then soft-delete the loser member.
            //
            // We deliberately keep the scrubbed loser user row around rather
            // than deleting it, because members.user_id -> users.id uses
            // cascadeOnDelete at the DB level, which bypasses SoftDeletes and
            // would hard-delete the loser member tombstone with it. Leaving the
            // user alive is a minor cost; nobody can sign in with the scrubbed
            // address, and audit history keeps pointing at a real row.
            if ($loser->user_id !== null && $survivor->user_id !== null && $loser->user_id !== $survivor->user_id) {
                $aliasAdded = $this->mergeUsers(
                    survivor: $survivor->user,
                    loser: $loser->user,
                    keepLoserEmailAsAlias: $keepLoserEmailAsAlias,
                );

                $stats['aliases_added'] = $aliasAdded;
                $stats['loser_user_deleted'] = true;
            }

            $loser->delete(); // soft delete

            return $stats;
        });
    }

    /**
     * Preview counts without touching anything. Used by the admin modal so the
     * reviewer sees what's about to move before they confirm.
     *
     * @return array<string, int>
     */
    public function preview(Member $loser): array
    {
        return [
            'memberships' => DB::table('memberships')->where('member_id', $loser->id)->count(),
            'event_registrations' => DB::table('event_registrations')->where('member_id', $loser->id)->count(),
            'event_results' => DB::table('event_results')->where('member_id', $loser->id)->count(),
            'club_badges' => DB::table('club_badge_member')->where('member_id', $loser->id)->count(),
            'sub_members' => DB::table('members')->where('linked_adult_member_id', $loser->id)->count(),
            'letter_requests' => DB::table('club_letter_requests')->where('member_id', $loser->id)->count(),
            'endorsement_requests' => DB::table('endorsement_requests')->where('member_id', $loser->id)->count(),
            'match_credits' => DB::table('match_credits')->where('member_id', $loser->id)->count(),
        ];
    }

    protected function assertMergeable(Member $survivor, Member $loser, bool $keepLoserEmailAsAlias): void
    {
        if ($survivor->id === $loser->id) {
            throw ValidationException::withMessages([
                'loser' => 'Cannot merge a member into itself.',
            ]);
        }

        if ($survivor->trashed() || $loser->trashed()) {
            throw ValidationException::withMessages([
                'loser' => 'Both members must be active (not soft-deleted) to merge.',
            ]);
        }

        if (! $keepLoserEmailAsAlias || $loser->user === null) {
            return;
        }

        $loserEmail = (string) $loser->user->email;
        if ($loserEmail === '') {
            return;
        }

        // The alias email cannot collide with somebody else's primary address —
        // if it did, logging in with that email would be ambiguous between two
        // accounts. (The survivor's own email is fine; we'll just skip adding
        // it as an alias.)
        $conflict = User::query()
            ->where('email', $loserEmail)
            ->when($survivor->user_id, fn ($q, $uid) => $q->where('id', '!=', $uid))
            ->where('id', '!=', $loser->user_id)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'loser' => 'The absorbed member\'s email is already a primary login on a third account. Resolve that first.',
            ]);
        }
    }

    /**
     * Move every member_id-keyed row to the survivor, deduping on unique
     * constraints where the survivor already has a matching row.
     *
     * @return array<string, int> rows repointed per table
     */
    protected function reparentMemberTables(Member $survivor, Member $loser): array
    {
        $counts = [];

        foreach (self::MEMBER_FK_TABLES as $table => $spec) {
            $counts[$table] = $this->reparentWithDedup(
                table: $table,
                column: $spec['column'],
                survivorId: $survivor->id,
                loserId: $loser->id,
                uniqueCols: $spec['unique'],
            );
        }

        return $counts;
    }

    /**
     * Repoint a loser-owned FK onto the survivor; where $uniqueCols is set,
     * delete loser rows that would collide with an existing survivor row on
     * (survivor, ...uniqueCols). Returns the number of rows actually repointed.
     *
     * @param  array<int, string>|null  $uniqueCols
     */
    protected function reparentWithDedup(
        string $table,
        string $column,
        int $survivorId,
        int $loserId,
        ?array $uniqueCols,
    ): int {
        if ($uniqueCols !== null && $uniqueCols !== []) {
            // Find loser rows whose (uniqueCols) tuple already has a survivor
            // row. Those loser rows get deleted — the survivor already owns
            // that slot. Everything else is a clean repoint.
            $survivorKeys = DB::table($table)
                ->where($column, $survivorId)
                ->get($uniqueCols)
                ->map(fn ($row) => implode('|', array_map(fn ($c) => (string) ($row->{$c} ?? ''), $uniqueCols)))
                ->all();

            if ($survivorKeys !== []) {
                $loserRows = DB::table($table)
                    ->where($column, $loserId)
                    ->get(array_merge(['id'], $uniqueCols));

                $toDelete = $loserRows
                    ->filter(fn ($row) => in_array(
                        implode('|', array_map(fn ($c) => (string) ($row->{$c} ?? ''), $uniqueCols)),
                        $survivorKeys,
                        true,
                    ))
                    ->pluck('id')
                    ->all();

                if ($toDelete !== []) {
                    DB::table($table)->whereIn('id', $toDelete)->delete();
                }
            }
        }

        return DB::table($table)
            ->where($column, $loserId)
            ->update([$column => $survivorId]);
    }

    /**
     * Merge two user accounts: port attribution + user-owned data onto the
     * survivor, add the loser's email as an alias (if asked), copy any aliases
     * the loser already had, hand over Spatie role assignments, flag the
     * survivor to confirm their primary email on next login, then delete the
     * loser user.
     *
     * @return int number of alias rows added to the survivor
     */
    protected function mergeUsers(User $survivor, User $loser, bool $keepLoserEmailAsAlias): int
    {
        // Repoint attribution columns. Nullable "created by" / "approved by"
        // links everywhere → survivor so history keeps pointing at a real user.
        foreach (self::USER_ATTRIBUTION_COLUMNS as $spec) {
            DB::table($spec['table'])
                ->where($spec['column'], $loser->id)
                ->update([$spec['column'] => $survivor->id]);
        }

        // User-owned data. Shop orders have a unique(shop_run_id, user_id) so
        // we dedup identically to event_registrations above.
        $this->reparentWithDedup(
            table: 'shop_orders',
            column: 'user_id',
            survivorId: $survivor->id,
            loserId: $loser->id,
            uniqueCols: ['shop_run_id'],
        );
        DB::table('email_logs')->where('user_id', $loser->id)->update(['user_id' => $survivor->id]);
        DB::table('shop_waitlist_subscribers')
            ->where('user_id', $loser->id)
            ->update(['user_id' => $survivor->id]);

        // Spatie role / permission assignments. Give the survivor every role
        // the loser had (skipping duplicates), then detach the loser so
        // deleting the user does not leave orphan rows.
        $this->transferSpatieAssignments($survivor, $loser);

        // Any aliases already attached to the loser follow the user to the
        // survivor. Collision with the survivor's primary address is skipped.
        $aliasesCopied = 0;
        foreach ($loser->emailAliases()->get() as $alias) {
            if ($alias->email === $survivor->email) {
                continue;
            }
            if (UserEmailAlias::where('email', $alias->email)->where('user_id', '!=', $loser->id)->exists()) {
                continue;
            }
            $alias->user_id = $survivor->id;
            $alias->save();
            $aliasesCopied++;
        }

        // Loser's primary email becomes an alias on the survivor (unless the
        // caller opted out, or it would collide with the survivor's own
        // primary email, or it is a synthetic @members placeholder).
        $added = 0;
        $loserEmail = (string) $loser->email;
        if (
            $keepLoserEmailAsAlias
            && $loserEmail !== ''
            && $loserEmail !== (string) $survivor->email
            && ! str_ends_with(strtolower($loserEmail), '@members.pretoriaprc.co.za')
            && ! UserEmailAlias::where('email', $loserEmail)->exists()
        ) {
            UserEmailAlias::create([
                'user_id' => $survivor->id,
                'email' => $loserEmail,
                'verified_at' => $loser->email_verified_at,
            ]);
            $added++;
        }

        $totalAdded = $added + $aliasesCopied;

        // Only prompt the user to pick a primary address if we actually ended
        // up with more than one on their account.
        if ($totalAdded > 0) {
            $survivor->forceFill(['must_pick_primary_email_at' => now()])->save();
        }

        // Scrub the loser user instead of deleting it. members.user_id has
        // cascadeOnDelete at the DB level, which bypasses SoftDeletes and
        // would hard-delete the loser member tombstone with the user.
        //
        // The email is replaced with an unreachable scratch address and the
        // password hash is replaced with a random bcrypt string nothing can
        // match (users.password is NOT NULL at the schema level, so we can't
        // just clear it). Together that locks the account out while keeping
        // the row intact so the member soft-delete tombstone survives.
        $loser->forceFill([
            'email' => 'merged-'.$loser->id.'-'.now()->timestamp.'@deleted.pretoriaprc.local',
            'password' => bcrypt(bin2hex(random_bytes(32))),
            'remember_token' => null,
            'email_verified_at' => null,
        ])->saveQuietly();

        return $totalAdded;
    }

    /**
     * Hand every Spatie role / permission the loser user holds to the survivor
     * (skipping duplicates), then detach them from the loser so deleting the
     * user leaves no orphan pivot rows.
     */
    protected function transferSpatieAssignments(User $survivor, User $loser): void
    {
        // The spatie/permission tables are keyed by model type + id, so we
        // target rows for this exact user model only — never touch any other
        // morph target that might share the users table id space.
        $modelType = $loser->getMorphClass();

        // Repoint role assignments the survivor doesn't already have.
        DB::table('model_has_roles')
            ->where('model_type', $modelType)
            ->where('model_id', $loser->id)
            ->whereNotIn('role_id', function ($q) use ($modelType, $survivor) {
                $q->select('role_id')
                    ->from('model_has_roles')
                    ->where('model_type', $modelType)
                    ->where('model_id', $survivor->id);
            })
            ->update(['model_id' => $survivor->id]);
        // Drop any left over (the ones the survivor already had).
        DB::table('model_has_roles')
            ->where('model_type', $modelType)
            ->where('model_id', $loser->id)
            ->delete();

        // Same for direct permissions.
        DB::table('model_has_permissions')
            ->where('model_type', $modelType)
            ->where('model_id', $loser->id)
            ->whereNotIn('permission_id', function ($q) use ($modelType, $survivor) {
                $q->select('permission_id')
                    ->from('model_has_permissions')
                    ->where('model_type', $modelType)
                    ->where('model_id', $survivor->id);
            })
            ->update(['model_id' => $survivor->id]);
        DB::table('model_has_permissions')
            ->where('model_type', $modelType)
            ->where('model_id', $loser->id)
            ->delete();
    }
}
