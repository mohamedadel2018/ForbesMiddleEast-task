<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'rating' => fake()->optional(0.6)->numberBetween(1, 5),
            'is_approved' => fake()->boolean(85),
            'approved_at' => fake()->optional(0.85)->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
