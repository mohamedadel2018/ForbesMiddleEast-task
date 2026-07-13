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

class ArticleRelationFieldFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_related_author_name(): void
    {
        $matchingAuthor = Author::factory()->create(['name' => 'Sarah Johnson']);
        $otherAuthor = Author::factory()->create(['name' => 'John Smith']);

        Article::factory()->for($matchingAuthor)->create(['title' => 'Match']);
        Article::factory()->for($otherAuthor)->create(['title' => 'Other']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'author.name', 'operator' => 'contains', 'value' => 'Sarah'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.author.name', 'Sarah Johnson');
    }

    public function test_filters_by_nested_category_parent_name(): void
    {
        $parent = Category::factory()->create(['name' => 'Business']);
        $child = Category::factory()->create(['name' => 'Startups', 'parent_id' => $parent->id]);
        $otherCategory = Category::factory()->create(['name' => 'Sports']);

        Article::factory()->for($child, 'category')->create(['title' => 'Startup Article']);
        Article::factory()->for($otherCategory, 'category')->create(['title' => 'Sports Article']);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'category.parent.name', 'operator' => 'eq', 'value' => 'Business'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Startup Article');
    }

    public function test_filters_by_many_to_many_tag_names(): void
    {
        $article = Article::factory()->create();
        $otherArticle = Article::factory()->create();

        $techTag = Tag::factory()->create(['name' => 'Technology']);
        $financeTag = Tag::factory()->create(['name' => 'Finance']);

        $article->tags()->attach($techTag);
        $otherArticle->tags()->attach($financeTag);

        $response = $this->getJson('/api/v1/articles?'.http_build_query([
            'filters' => [
                ['field' => 'tags.name', 'operator' => 'eq', 'value' => 'Technology'],
            ],
        ]));

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($article->id, $response->json('data.0.id'));
    }

    public function test_comment_filters_by_nested_article_author(): void
    {
        $author = Author::factory()->create(['name' => 'Nested Author']);
        $article = Article::factory()->for($author)->create(['title' => 'Nested Article']);
        $otherArticle = Article::factory()->create();

        Comment::factory()->for($article)->create(['body' => 'Relevant comment']);
        Comment::factory()->for($otherArticle)->create(['body' => 'Other comment']);

        $response = $this->getJson('/api/v1/comments?'.http_build_query([
            'filters' => [
                ['field' => 'article.author.name', 'operator' => 'eq', 'value' => 'Nested Author'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.body', 'Relevant comment');
    }

    public function test_author_filters_by_related_user_email(): void
    {
        $user = User::factory()->create(['email' => 'editor@forbes.test']);
        $author = Author::factory()->for($user)->create();
        Author::factory()->create();

        $response = $this->getJson('/api/v1/authors?'.http_build_query([
            'filters' => [
                ['field' => 'user.email', 'operator' => 'eq', 'value' => 'editor@forbes.test'],
            ],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $author->id);
    }
}
