<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_creates_a_new_post(): void
    {
        $response = $this->postJson('/internal/posts', [
            'title' => 'Test Post',
            'content' => '# Hello World',
            'status' => 'draft',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Test Post')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'content' => '# Hello World',
            'status' => 'draft',
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function it_auto_generates_slug_from_title(): void
    {
        $response = $this->postJson('/internal/posts', [
            'title' => 'Hello World Test',
            'content' => 'Content',
            'status' => 'draft',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'hello-world-test');
    }

    #[Test]
    public function it_creates_post_with_custom_slug(): void
    {
        $response = $this->postJson('/internal/posts', [
            'title' => 'Test Post',
            'content' => 'Content',
            'slug' => 'custom-slug',
            'status' => 'draft',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'custom-slug');
    }

    #[Test]
    public function it_prevents_duplicate_slugs(): void
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
        ]);

        $response = $this->postJson('/internal/posts', [
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => 'draft',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertCreated();
        $slug = $response->json('data.slug');
        $this->assertNotEquals('test-post', $slug);
        $this->assertStringStartsWith('test-post-', $slug);
    }

    #[Test]
    public function it_retrieves_post_list(): void
    {
        Post::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/internal/posts', [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'slug', 'status'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function it_filters_posts_by_status(): void
    {
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/internal/posts?status=draft', [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_retrieves_published_posts_only(): void
    {
        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/internal/posts/published');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_retrieves_single_post_by_id(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/internal/posts/{$post->id}", [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.title', $post->title);
    }

    #[Test]
    public function it_retrieves_single_post_by_slug(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'test-slug',
        ]);

        $response = $this->getJson('/internal/posts/slug/test-slug', [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.slug', 'test-slug');
    }

    #[Test]
    public function it_updates_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/internal/posts/{$post->id}", [
            'title' => 'Updated Title',
            'content' => 'Updated content',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.content', 'Updated content');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    #[Test]
    public function it_prevents_unauthorized_update(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/internal/posts/{$post->id}", [
            'title' => 'Hacked Title',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');
    }

    #[Test]
    public function it_deletes_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/internal/posts/{$post->id}", [], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    #[Test]
    public function it_prevents_unauthorized_delete(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/internal/posts/{$post->id}", [], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    #[Test]
    public function it_publishes_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson("/internal/posts/{$post->id}/publish", [], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);

        $post->refresh();
        $this->assertNotNull($post->published_at);
    }

    #[Test]
    public function it_unpublishes_post(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->postJson("/internal/posts/{$post->id}/unpublish", [], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'draft');
    }

    #[Test]
    public function it_returns_user_post_stats(): void
    {
        Post::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'private',
        ]);

        $response = $this->getJson('/internal/posts/stats', [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.total', 10)
            ->assertJsonPath('data.published', 3)
            ->assertJsonPath('data.draft', 5)
            ->assertJsonPath('data.private', 2);
    }

    #[Test]
    public function it_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/internal/posts', [
            'title' => '',
        ], [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    #[Test]
    public function it_returns_not_found_for_non_existent_post(): void
    {
        $response = $this->getJson('/internal/posts/99999', [
            'X-User-Id' => $this->user->id,
        ]);

        $response->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }
}
