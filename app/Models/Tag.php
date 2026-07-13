<?php

namespace App\Models;

use App\Concerns\HasAdvancedFilters;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasAdvancedFilters, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'usage_count' => 'integer',
        ];
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }

    public static function filterableFields(): array
    {
        return ['name', 'slug', 'color', 'usage_count', 'created_at'];
    }

    public static function filterableRelations(): array
    {
        return [];
    }

    public static function searchableFields(): array
    {
        return ['name', 'slug'];
    }

    public static function searchableRelations(): array
    {
        return [];
    }

    public static function sortableFields(): array
    {
        return ['name', 'usage_count', 'created_at'];
    }

    public static function defaultRelations(): array
    {
        return [];
    }
}
