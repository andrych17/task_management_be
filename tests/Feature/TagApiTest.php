<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    #[Test]
    public function authenticated_user_can_get_their_tags()
    {
        Tag::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'user_id', 'name', 'created_at', 'updated_at']
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function tags_can_be_searched_by_name()
    {
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'urgent']);
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'backend']);
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'frontend']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags?search=end');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $tagNames = array_column($response->json('data'), 'name');
        $this->assertContains('backend', $tagNames);
        $this->assertContains('frontend', $tagNames);
    }

    #[Test]
    public function tags_are_sorted_alphabetically()
    {
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'zebra']);
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'alpha']);
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'beta']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('alpha', $data[0]['name']);
        $this->assertEquals('beta', $data[1]['name']);
        $this->assertEquals('zebra', $data[2]['name']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_tags()
    {
        $response = $this->getJson('/api/tags');
        $response->assertStatus(401);
    }

    #[Test]
    public function user_only_sees_their_own_tags()
    {
        $otherUser = User::factory()->create();

        Tag::factory()->count(2)->create(['user_id' => $this->user->id]);
        Tag::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function empty_search_returns_all_tags()
    {
        Tag::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags?search=');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function search_is_case_insensitive()
    {
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'URGENT']);
        Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'Backend']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tags?search=urgent');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertEquals('URGENT', $response->json('data.0.name'));
    }
}
