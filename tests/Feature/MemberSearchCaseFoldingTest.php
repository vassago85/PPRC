<?php

use App\Filament\Admin\Support\MemberSearch;
use App\Filament\Admin\Support\SearchTerm;
use App\Models\Member;
use App\Models\MembershipPayment;

/**
 * The suite runs on SQLite, whose LIKE is case-insensitive for ASCII, so a
 * case-sensitivity bug here would pass locally and fail in production on
 * Postgres. Compiling the same query against the Postgres grammar catches it.
 * Nothing connects — toSql() only needs the grammar, not a live server.
 */
beforeEach(function () {
    config()->set('database.connections.pgsql_grammar_only', [
        'driver' => 'pgsql',
        'host' => '127.0.0.1',
        'port' => 5432,
        'database' => 'unused',
        'username' => 'unused',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'search_path' => 'public',
    ]);
});

function memberSearchSqlOn(string $connection, string $search): array
{
    $query = Member::on($connection);

    MemberSearch::apply($query, $search);

    return [$query->toSql(), $query->getBindings()];
}

it('lower-cases both column and term on postgres so mixed-case names match', function () {
    [$sql, $bindings] = memberSearchSqlOn('pgsql_grammar_only', 'Van Zyl');

    // Every comparison must fold the column, or Postgres LIKE stays
    // case-sensitive and "van zyl" never finds "Van Zyl".
    expect($sql)
        ->toContain('lower("first_name"')
        ->toContain('lower("last_name"')
        ->toContain('lower("known_as"')
        ->toContain('lower("membership_number"')
        ->toContain('lower("email"');

    // ...and the term has to be folded to match.
    expect($bindings)->each->toBe('%van zyl%');
});

it('folds any hand-written table search built on SearchTerm', function () {
    $query = MembershipPayment::on('pgsql_grammar_only')
        ->where(
            ($term = SearchTerm::make(MembershipPayment::on('pgsql_grammar_only'), 'PPRC-Ref'))
                ->column('reference'),
            'like',
            $term->contains(),
        );

    expect($query->toSql())->toContain('lower("reference"');
    expect($query->getBindings())->toBe(['%pprc-ref%']);
});

it('searches the columns an admin expects on the default connection', function () {
    [$sql] = memberSearchSqlOn(config('database.default'), 'zyl');

    expect($sql)
        ->toContain('first_name')
        ->toContain('last_name')
        ->toContain('known_as')
        ->toContain('membership_number')
        ->toContain('users');
});
