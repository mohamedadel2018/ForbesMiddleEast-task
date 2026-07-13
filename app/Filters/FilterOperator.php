<?php

namespace App\Filters;

enum FilterOperator: string
{
    case Eq = 'eq';
    case Ne = 'ne';
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case In = 'in';
    case NotIn = 'not_in';
    case Empty = 'empty';
    case NotEmpty = 'not_empty';

    public static function tryFromString(string $operator): ?self
    {
        return self::tryFrom(strtolower($operator));
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
