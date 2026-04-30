<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExcoMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name', 'position', 'bio',
        'email', 'phone', 'photo_path',
        'sort_order', 'term_started_on', 'term_ends_on',
        'is_current', 'linked_user_id',
    ];

    protected $casts = [
        'term_started_on' => 'date',
        'term_ends_on' => 'date',
        'is_current' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Aliases for legacy / informal position spellings that still need to
     * map to a real Spatie role slug (e.g. "Chairman" used to be the position
     * label before the committee standardised on "Chairperson").
     */
    protected const POSITION_ALIASES = [
        'chairman' => 'chairperson',
        'chair' => 'chairperson',
        'vice chairman' => 'vice_chair',
        'vice chairperson' => 'vice_chair',
        'admin officer' => 'admin',
        'admin assistant' => 'admin',
    ];

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('is_current', true)->orderBy('sort_order');
    }

    /**
     * Map this ExCo member's `position` label to a Spatie role slug, using
     * the canonical User::COMMITTEE_ROLE_LABELS list and a small alias table
     * for legacy spellings. Returns null if the position is freetext that
     * doesn't correspond to a committee role (e.g. "Honorary Member").
     */
    public function roleSlug(): ?string
    {
        if (! $this->position) {
            return null;
        }

        $needle = strtolower(trim($this->position));

        foreach (User::COMMITTEE_ROLE_LABELS as $slug => $label) {
            if ($needle === strtolower($label) || $needle === $slug) {
                return $slug;
            }
        }

        return self::POSITION_ALIASES[$needle] ?? null;
    }

    protected static function booted(): void
    {
        static::saved(function (ExcoMember $exco): void {
            $exco->syncLinkedUserRole();
        });
    }

    /**
     * If this ExCo seat is current and points at a real user, ensure that
     * user holds the matching committee role so admin/portal access actually
     * follows the committee roster. We only ADD the role here — revoking
     * remains explicit (via the user-roles admin) so terms ending or seat
     * shuffles never silently lock somebody out mid-task.
     */
    public function syncLinkedUserRole(): void
    {
        if (! $this->is_current || ! $this->linked_user_id) {
            return;
        }

        $slug = $this->roleSlug();
        if (! $slug) {
            return;
        }

        $user = $this->linkedUser()->first();
        if (! $user) {
            return;
        }

        if (! $user->hasRole($slug)) {
            $user->assignRole($slug);
        }
    }
}
