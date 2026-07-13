<?php

namespace App\Concerns;

use App\Filters\AdvancedFilterBuilder;
use Illuminate\Database\Eloquent\Builder;

trait HasAdvancedFilters
{
    /**
     * @return list<string>
     */
    abstract public static function filterableFields(): array;

    /**
     * @return array<string, class-string>
     */
    abstract public static function filterableRelations(): array;

    /**
     * @return list<string>
     */
    abstract public static function searchableFields(): array;

    /**
     * @return array<string, list<string>|string>
     */
    abstract public static function searchableRelations(): array;

    /**
     * @return list<string>
     */
    abstract public static function sortableFields(): array;

    /**
     * @return list<string>
     */
    abstract public static function defaultRelations(): array;

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAdvancedList(
        Builder $query,
        array $filters = [],
        ?string $search = null,
        ?string $sort = null,
    ): Builder {
        return app(AdvancedFilterBuilder::class)->apply(
            $query,
            static::class,
            $filters,
            $search,
            $sort,
        );
    }
}
