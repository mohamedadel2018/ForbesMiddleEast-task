<?php

namespace App\Models;

use App\Concerns\HasAdvancedFilters;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasAdvancedFilters, HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public static function filterableFields(): array
    {
        return ['name', 'slug', 'description', 'sort_order', 'is_active', 'created_at'];
    }

    public static function filterableRelations(): array
    {
        return [
            'parent' => Category::class,
        ];
    }

    public static function searchableFields(): array
    {
        return ['name', 'description'];
    }

    public static function searchableRelations(): array
    {
        return [
            'parent' => ['name'],
        ];
    }

    public static function sortableFields(): array
    {
        return ['name', 'sort_order', 'created_at', 'parent.name'];
    }

    public static function defaultRelations(): array
    {
        return ['parent'];
    }
}
