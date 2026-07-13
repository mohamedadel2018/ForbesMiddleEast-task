<?php

namespace App\Models;

use App\Concerns\HasAdvancedFilters;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasAdvancedFilters, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'slug',
        'bio',
        'articles_count',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'articles_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public static function filterableFields(): array
    {
        return ['name', 'email', 'slug', 'bio', 'articles_count', 'rating', 'created_at'];
    }

    public static function filterableRelations(): array
    {
        return [
            'user' => User::class,
        ];
    }

    public static function searchableFields(): array
    {
        return ['name', 'email', 'bio'];
    }

    public static function searchableRelations(): array
    {
        return [
            'user' => ['name', 'email'],
        ];
    }

    public static function sortableFields(): array
    {
        return ['name', 'email', 'articles_count', 'rating', 'created_at', 'user.name', 'user.email'];
    }

    public static function defaultRelations(): array
    {
        return ['user'];
    }
}
