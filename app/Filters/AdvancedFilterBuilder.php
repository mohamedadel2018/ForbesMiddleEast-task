<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AdvancedFilterBuilder
{
    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    public function apply(
        Builder $query,
        string $modelClass,
        array $filters = [],
        ?string $search = null,
        ?string $sort = null,
    ): Builder {
        $this->applyFilters($query, $modelClass, $filters);
        $this->applyGlobalSearch($query, $modelClass, $search);
        $this->applySorting($query, $modelClass, $sort);

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    public function applyFilters(Builder $query, string $modelClass, array $filters): void
    {
        foreach ($filters as $filter) {
            if (! is_array($filter)) {
                continue;
            }

            $field = $filter['field'] ?? null;
            $operator = FilterOperator::tryFromString((string) ($filter['operator'] ?? ''));

            if (! $field || ! $operator) {
                continue;
            }

            $value = $filter['value'] ?? null;

            if (in_array($operator, [FilterOperator::In, FilterOperator::NotIn], true) && is_string($value)) {
                $value = array_map('trim', explode(',', $value));
            }

            if (str_contains($field, '.')) {
                $this->applyRelationFilter($query, $modelClass, $field, $operator, $value);

                continue;
            }

            $this->applyDirectFilter($query, $modelClass, $field, $operator, $value);
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    public function applyGlobalSearch(Builder $query, string $modelClass, ?string $search): void
    {
        if ($search === null || trim($search) === '') {
            return;
        }

        $searchable = $modelClass::searchableFields();
        $relationSearch = $modelClass::searchableRelations();

        if ($searchable === [] && $relationSearch === []) {
            return;
        }

        $term = '%'.addcslashes(trim($search), '%_\\').'%';

        $query->where(function (Builder $builder) use ($searchable, $relationSearch, $term, $modelClass): void {
            foreach ($searchable as $field) {
                $builder->orWhere($field, 'like', $term);
            }

            foreach ($relationSearch as $relationPath => $fields) {
                $builder->orWhereHas($relationPath, function (Builder $relationQuery) use ($fields, $term, $modelClass, $relationPath): void {
                    $this->applyNestedRelationSearch($relationQuery, $modelClass, $relationPath, $fields, $term);
                });
            }
        });
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    public function applySorting(Builder $query, string $modelClass, ?string $sort): void
    {
        if ($sort === null || trim($sort) === '') {
            return;
        }

        $sortable = $modelClass::sortableFields();

        foreach (explode(',', $sort) as $sortField) {
            $sortField = trim($sortField);

            if ($sortField === '') {
                continue;
            }

            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if (! in_array($sortField, $sortable, true)) {
                continue;
            }

            if (str_contains($sortField, '.')) {
                $this->applyRelationSort($query, $modelClass, $sortField, $direction);

                continue;
            }

            $query->orderBy($sortField, $direction);
        }
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function applyDirectFilter(
        Builder $query,
        string $modelClass,
        string $field,
        FilterOperator $operator,
        mixed $value,
    ): void {
        $this->assertFilterableField($modelClass, $field);

        match ($operator) {
            FilterOperator::Eq => $query->where($field, '=', $value),
            FilterOperator::Ne => $query->where($field, '!=', $value),
            FilterOperator::Contains => $query->where($field, 'like', '%'.addcslashes((string) $value, '%_\\').'%'),
            FilterOperator::StartsWith => $query->where($field, 'like', addcslashes((string) $value, '%_\\').'%'),
            FilterOperator::EndsWith => $query->where($field, 'like', '%'.addcslashes((string) $value, '%_\\')),
            FilterOperator::Gt => $query->where($field, '>', $value),
            FilterOperator::Gte => $query->where($field, '>=', $value),
            FilterOperator::Lt => $query->where($field, '<', $value),
            FilterOperator::Lte => $query->where($field, '<=', $value),
            FilterOperator::In => $query->whereIn($field, Arr::wrap($value)),
            FilterOperator::NotIn => $query->whereNotIn($field, Arr::wrap($value)),
            FilterOperator::Empty => $query->where(function (Builder $builder) use ($field): void {
                $builder->whereNull($field)->orWhere($field, '=', '');
            }),
            FilterOperator::NotEmpty => $query->where(function (Builder $builder) use ($field): void {
                $builder->whereNotNull($field)->where($field, '!=', '');
            }),
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function applyRelationFilter(
        Builder $query,
        string $modelClass,
        string $fieldPath,
        FilterOperator $operator,
        mixed $value,
    ): void {
        $segments = explode('.', $fieldPath);
        $column = array_pop($segments);
        $relationPath = implode('.', $segments);

        $this->assertFilterableRelation($modelClass, $relationPath);

        if ($operator === FilterOperator::Empty) {
            $query->where(function (Builder $builder) use ($relationPath, $column): void {
                $builder->whereDoesntHave($relationPath)
                    ->orWhereHas($relationPath, fn (Builder $relationQuery) => $this->applyDirectColumnConstraint($relationQuery, $column, FilterOperator::Empty, null));
            });

            return;
        }

        if ($operator === FilterOperator::NotEmpty) {
            $query->whereHas($relationPath, fn (Builder $relationQuery) => $this->applyDirectColumnConstraint($relationQuery, $column, FilterOperator::NotEmpty, null));

            return;
        }

        $query->whereHas($relationPath, fn (Builder $relationQuery) => $this->applyDirectColumnConstraint($relationQuery, $column, $operator, $value));
    }

    protected function applyDirectColumnConstraint(
        Builder $query,
        string $column,
        FilterOperator $operator,
        mixed $value,
    ): void {
        match ($operator) {
            FilterOperator::Eq => $query->where($column, '=', $value),
            FilterOperator::Ne => $query->where($column, '!=', $value),
            FilterOperator::Contains => $query->where($column, 'like', '%'.addcslashes((string) $value, '%_\\').'%'),
            FilterOperator::StartsWith => $query->where($column, 'like', addcslashes((string) $value, '%_\\').'%'),
            FilterOperator::EndsWith => $query->where($column, 'like', '%'.addcslashes((string) $value, '%_\\')),
            FilterOperator::Gt => $query->where($column, '>', $value),
            FilterOperator::Gte => $query->where($column, '>=', $value),
            FilterOperator::Lt => $query->where($column, '<', $value),
            FilterOperator::Lte => $query->where($column, '<=', $value),
            FilterOperator::In => $query->whereIn($column, Arr::wrap($value)),
            FilterOperator::NotIn => $query->whereNotIn($column, Arr::wrap($value)),
            FilterOperator::Empty => $query->where(function (Builder $builder) use ($column): void {
                $builder->whereNull($column)->orWhere($column, '=', '');
            }),
            FilterOperator::NotEmpty => $query->where(function (Builder $builder) use ($column): void {
                $builder->whereNotNull($column)->where($column, '!=', '');
            }),
        };
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function applyRelationSort(Builder $query, string $modelClass, string $fieldPath, string $direction): void
    {
        $segments = explode('.', $fieldPath);
        $column = array_pop($segments);
        $relationPath = implode('.', $segments);

        $this->assertFilterableRelation($modelClass, $relationPath);

        $relation = $query->getModel()->{$this->firstRelationName($relationPath)}();
        $related = $relation->getRelated();
        $relatedTable = $related->getTable();
        $parentTable = $query->getModel()->getTable();

        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            $foreignKey = $relation->getForeignKeyName();
            $ownerKey = $relation->getOwnerKeyName();
            $alias = str_replace('.', '_', $relationPath);

            $query->leftJoin("{$relatedTable} as {$alias}", "{$parentTable}.{$foreignKey}", '=', "{$alias}.{$ownerKey}")
                ->orderBy("{$alias}.{$column}", $direction)
                ->select("{$parentTable}.*");

            return;
        }

        $query->orderBy(
            $related->newQuery()
                ->select($column)
                ->whereColumn($relatedTable.'.'.$relation->getForeignKeyName(), $parentTable.'.'.$relation->getLocalKeyName())
                ->limit(1),
            $direction,
        );
    }

    /**
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function applyNestedRelationSearch(
        Builder $query,
        string $modelClass,
        string $relationPath,
        array|string $fields,
        string $term,
    ): void {
        $fields = Arr::wrap($fields);

        if (str_contains($relationPath, '.')) {
            $segments = explode('.', $relationPath);
            $first = array_shift($segments);
            $remaining = implode('.', $segments);

            $query->whereHas($remaining, function (Builder $nested) use ($fields, $term): void {
                $nested->where(function (Builder $builder) use ($fields, $term): void {
                    foreach ($fields as $field) {
                        $builder->orWhere($field, 'like', $term);
                    }
                });
            });

            return;
        }

        $query->where(function (Builder $builder) use ($fields, $term): void {
            foreach ($fields as $field) {
                $builder->orWhere($field, 'like', $term);
            }
        });
    }

    /**
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function assertFilterableField(string $modelClass, string $field): void
    {
        if (! in_array($field, $modelClass::filterableFields(), true)) {
            throw new InvalidArgumentException("Field [{$field}] is not filterable on [{$modelClass}].");
        }
    }

    /**
     * @param  class-string<Model&\App\Concerns\HasAdvancedFilters>  $modelClass
     */
    protected function assertFilterableRelation(string $modelClass, string $relationPath): void
    {
        if (! array_key_exists($relationPath, $modelClass::filterableRelations())) {
            throw new InvalidArgumentException("Relation [{$relationPath}] is not filterable on [{$modelClass}].");
        }
    }

    protected function firstRelationName(string $relationPath): string
    {
        return explode('.', $relationPath)[0];
    }
}
