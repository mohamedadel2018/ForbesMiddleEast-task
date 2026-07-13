<?php

namespace App\Models;

use App\Concerns\HasAdvancedFilters;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasAdvancedFilters, HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'body',
        'rating',
        'is_approved',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public static function filterableFields(): array
    {
        return ['body', 'rating', 'is_approved', 'approved_at', 'created_at'];
    }

    public static function filterableRelations(): array
    {
        return [
            'article' => Article::class,
            'article.author' => Author::class,
            'article.category' => Category::class,
            'user' => User::class,
        ];
    }

    public static function searchableFields(): array
    {
        return ['body'];
    }

    public static function searchableRelations(): array
    {
        return [
            'article' => ['title'],
            'user' => ['name', 'email'],
        ];
    }

    public static function sortableFields(): array
    {
        return ['rating', 'created_at', 'approved_at', 'article.title', 'user.name'];
    }

    public static function defaultRelations(): array
    {
        return ['article.author', 'user'];
    }
}
