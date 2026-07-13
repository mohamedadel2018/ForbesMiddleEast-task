<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'author_id' => Author::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('######'),
            'excerpt' => fake()->optional(0.9)->paragraph(),
            'content' => fake()->paragraphs(5, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'published_at' => fake()->optional(0.75)->dateTimeBetween('-3 years', 'now'),
            'view_count' => fake()->numberBetween(0, 500000),
            'rating' => fake()->optional(0.7)->randomFloat(2, 1, 5),
            'is_featured' => fake()->boolean(10),
            'metadata' => fake()->optional(0.3)->passthrough([
                'source' => fake()->randomElement(['editorial', 'syndicated', 'partner']),
                'locale' => fake()->randomElement(['en', 'ar']),
            ]),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ]);
    }
}
