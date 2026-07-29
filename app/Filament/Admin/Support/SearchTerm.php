<?php

namespace App\Filament\Admin\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

use function Filament\Support\generate_search_column_expression;
use function Filament\Support\generate_search_term_expression;

/**
 * Case-folded column and term for a hand-written table search.
 *
 * Filament applies this folding automatically to columns marked
 * `->searchable()`, but a custom `searchable(query: ...)` closure is on its own.
 * That matters because LIKE is case-sensitive on Postgres: a plain
 * `where('last_name', 'like', '%van zyl%')` looks correct, passes on SQLite in
 * tests, and then fails to match "Van Zyl" in production.
 */
class SearchTerm
{
    private function __construct(
        private readonly mixed $connection,
        private readonly string $term,
    ) {}

    /** @param  Builder|\Illuminate\Database\Query\Builder  $query */
    public static function make($query, string $search): self
    {
        $connection = $query->getConnection();

        return new self(
            $connection,
            generate_search_term_expression($search, null, $connection),
        );
    }

    public function column(string $name): string|Expression
    {
        return generate_search_column_expression($name, null, $this->connection);
    }

    /** The bound value, wrapped for a "contains" match. */
    public function contains(): string
    {
        return '%'.$this->term.'%';
    }
}
