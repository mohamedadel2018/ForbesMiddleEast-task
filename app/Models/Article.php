<?php

namespace App\Models;

use App\Concerns\HasAdvancedFilters;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasAdvancedFilters, HasFactory;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'view_count',
        'rating',
        'is_featured',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'view_count' => 'integer',
            'rating' => 'decimal:2',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public static function filterableFields(): array
    {
        return [
            'title',
            'slug',
            'excerpt',
            'status',
            'published_at',
            'view_count',
            'rating',
            'is_featured',
            'created_at',
        ];
    }

    public static function filterableRelations(): array
    {
        return [
            'author' => Author::class,
            'category' => Category::class,
            'category.parent' => Category::class,
            'tags' => Tag::class,
            'comments' => Comment::class,
        ];
    }

    public static function searchableFields(): array
    {
        return ['title', 'excerpt', 'content', 'slug'];
    }

    public static function searchableRelations(): array
    {
        return [
            'author' => ['name', 'email'],
            'category' => ['name'],
            'tags' => ['name'],
        ];
    }

    public static function sortableFields(): array
    {
        return [
            'title',
            'status',
            'published_at',
            'view_count',
            'rating',
            'created_at',
            'author.name',
            'category.name',
        ];
    }

    public static function defaultRelations(): array
    {
        return ['author', 'category.parent', 'tags'];
    }
}
