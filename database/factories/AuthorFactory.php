<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'bio' => fake()->optional(0.7)->paragraph(),
            'articles_count' => fake()->numberBetween(0, 500),
            'rating' => fake()->optional(0.8)->randomFloat(2, 1, 5),
        ];
    }
}
