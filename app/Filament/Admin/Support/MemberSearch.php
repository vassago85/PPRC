<?php

namespace App\Filament\Admin\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * One definition of "search for a member", shared by the admin lists that hang
 * off a member relationship.
 *
 * Filament splits the search box on whitespace and requires every word to
 * match, so "coenie van tonder" arrives here as three separate calls and finds
 * the member across first name and surname without needing to concatenate them.
 */
class MemberSearch
{
    public static function apply(Builder $query, string $search): Builder
    {
        $term = SearchTerm::make($query, $search);

        return $query->where(fn (Builder $q) => $q
            ->where($term->column('first_name'), 'like', $term->contains())
            ->orWhere($term->column('last_name'), 'like', $term->contains())
            ->orWhere($term->column('known_as'), 'like', $term->contains())
            ->orWhere($term->column('membership_number'), 'like', $term->contains())
            ->orWhereHas('user', fn ($u) => $u
                ->where($term->column('email'), 'like', $term->contains())));
    }
}
