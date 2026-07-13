<?php

namespace Tests\Feature\Filtering;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleDirectFieldFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_text_contains_operator(): void
    {
        Article::factory()->create(['title' => 'Laravel Advanced Filtering Guide']);
        Article::factory()->create(['title' => 'PHP Basics']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'title', 'operator' => 'contains', 'value' => 'Filtering'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Advanced Filtering Guide');
    }

    public function test_filters_by_exact_status_match(): void
    {
        Article::factory()->create(['status' => 'published']);
        Article::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
            ],
        ]));

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('published', $response->json('data.0.status'));
    }

    public function test_filters_by_number_comparison(): void
    {
        Article::factory()->create(['view_count' => 50]);
        Article::factory()->create(['view_count' => 500]);
        Article::factory()->create(['view_count' => 5000]);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'view_count', 'operator' => 'gte', 'value' => 500],
            ],
        ]));

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filters_by_date_comparison(): void
    {
        Article::factory()->create(['published_at' => '2024-01-01 00:00:00']);
        Article::factory()->create(['published_at' => '2025-06-01 00:00:00']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'published_at', 'operator' => 'gte', 'value' => '2025-01-01'],
            ],
        ]));

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_filters_empty_and_not_empty_values(): void
    {
        Article::factory()->create(['excerpt' => null]);
        Article::factory()->create(['excerpt' => 'Has excerpt']);

        $emptyResponse = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'excerpt', 'operator' => 'empty', 'value' => null],
            ],
        ]));

        $notEmptyResponse = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'excerpt', 'operator' => 'not_empty', 'value' => null],
            ],
        ]));

        $emptyResponse->assertOk()->assertJsonCount(1, 'data');
        $notEmptyResponse->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_filters_by_multiple_selected_values(): void
    {
        Article::factory()->create(['status' => 'draft']);
        Article::factory()->create(['status' => 'published']);
        Article::factory()->create(['status' => 'archived']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'status', 'operator' => 'in', 'value' => 'draft,published'],
            ],
        ]));

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_supports_global_search_sorting_and_pagination(): void
    {
        Article::factory()->create(['title' => 'Alpha Forbes Feature', 'view_count' => 10]);
        Article::factory()->create(['title' => 'Beta Update', 'view_count' => 100]);
        Article::factory()->create(['title' => 'Gamma Forbes Report', 'view_count' => 1000]);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'search' => 'Forbes',
            'sort' => '-view_count',
            'per_page' => 1,
            'page' => 1,
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.title', 'Gamma Forbes Report');
    }
}
