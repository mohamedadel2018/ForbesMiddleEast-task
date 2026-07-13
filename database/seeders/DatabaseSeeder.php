<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding users...');
        User::factory(5000)->create();

        $this->command?->info('Seeding authors...');
        Author::factory(3000)->create();

        $this->command?->info('Seeding categories...');
        $rootCategories = Category::factory(50)->create();
        foreach ($rootCategories as $category) {
            Category::factory()->create(['parent_id' => $category->id]);
        }

        $this->command?->info('Seeding tags...');
        Tag::factory(500)->create();

        $authorIds = Author::query()->pluck('id')->all();
        $categoryIds = Category::query()->pluck('id')->all();
        $tagIds = Tag::query()->pluck('id')->all();
        $userIds = User::query()->pluck('id')->all();

        $this->command?->info('Bulk seeding 85,000 articles...');
        $this->seedArticlesInChunks(85000, $authorIds, $categoryIds);

        $articleIds = Article::query()->pluck('id')->all();

        $this->command?->info('Seeding article-tag pivot rows...');
        $this->seedArticleTags($articleIds, $tagIds);

        $this->command?->info('Bulk seeding 20,000 comments...');
        $this->seedCommentsInChunks(20000, $articleIds, $userIds);

        $total = DB::table('users')->count()
            + DB::table('authors')->count()
            + DB::table('categories')->count()
            + DB::table('tags')->count()
            + DB::table('articles')->count()
            + DB::table('article_tag')->count()
            + DB::table('comments')->count();

        $this->command?->info("Seeding complete. Total rows across core tables: {$total}");
    }

    /**
     * @param  list<int>  $authorIds
     * @param  list<int>  $categoryIds
     */
    protected function seedArticlesInChunks(int $total, array $authorIds, array $categoryIds): void
    {
        $chunkSize = 1000;
        $now = now();
        $statuses = ['draft', 'published', 'archived'];

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $rows = [];

            for ($i = 0; $i < $chunkSize && ($offset + $i) < $total; $i++) {
                $index = $offset + $i;
                $title = 'Article '.$index.' '.fake()->sentence(4);
                $status = $statuses[$index % count($statuses)];
                $publishedAt = $status === 'published'
                    ? $now->copy()->subDays($index % 1000)
                    : null;

                $rows[] = [
                    'author_id' => $authorIds[$index % count($authorIds)],
                    'category_id' => $categoryIds[$index % count($categoryIds)],
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.$index,
                    'excerpt' => fake()->sentence(12),
                    'content' => fake()->paragraphs(3, true),
                    'status' => $status,
                    'published_at' => $publishedAt,
                    'view_count' => $index % 500000,
                    'rating' => round(($index % 50) / 10, 2),
                    'is_featured' => $index % 17 === 0,
                    'metadata' => json_encode(['batch' => intdiv($index, $chunkSize)]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('articles')->insert($rows);
        }
    }

    /**
     * @param  list<int>  $articleIds
     * @param  list<int>  $tagIds
     */
    protected function seedArticleTags(array $articleIds, array $tagIds): void
    {
        $now = now();
        $rows = [];

        foreach ($articleIds as $index => $articleId) {
            $tagCount = ($index % 3) + 1;

            for ($t = 0; $t < $tagCount; $t++) {
                $rows[] = [
                    'article_id' => $articleId,
                    'tag_id' => $tagIds[($index + $t) % count($tagIds)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (count($rows) >= 2000) {
                DB::table('article_tag')->insertOrIgnore($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('article_tag')->insertOrIgnore($rows);
        }
    }

    /**
     * @param  list<int>  $articleIds
     * @param  list<int>  $userIds
     */
    protected function seedCommentsInChunks(int $total, array $articleIds, array $userIds): void
    {
        $chunkSize = 1000;
        $now = now();

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $rows = [];

            for ($i = 0; $i < $chunkSize && ($offset + $i) < $total; $i++) {
                $index = $offset + $i;
                $isApproved = $index % 5 !== 0;

                $rows[] = [
                    'article_id' => $articleIds[$index % count($articleIds)],
                    'user_id' => $userIds[$index % count($userIds)],
                    'parent_id' => null,
                    'body' => fake()->sentence(20),
                    'rating' => ($index % 5) + 1,
                    'is_approved' => $isApproved,
                    'approved_at' => $isApproved ? $now : null,
                    'created_at' => $now->copy()->subDays($index % 365),
                    'updated_at' => $now,
                ];
            }

            DB::table('comments')->insert($rows);
        }
    }
}
