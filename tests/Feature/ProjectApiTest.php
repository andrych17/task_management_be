<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProjectApiTest extends TestCase
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
    public function authenticated_user_can_get_their_projects()
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);
        Project::factory()->count(2)->create(); // Other user's projects

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'description', 'user_id', 'created_at', 'updated_at']
                ]
            ]);
    }

    #[Test]
    public function projects_can_be_searched_by_name()
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Website Redesign',
            'description' => 'Complete redesign'
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Mobile App',
            'description' => 'iOS and Android app'
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'API Integration',
            'description' => 'Backend API'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects?search=website');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertEquals('Website Redesign', $response->json('data.0.name'));
    }

    #[Test]
    public function projects_can_be_searched_by_description()
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Project A',
            'description' => 'API development'
        ]);

        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Project B',
            'description' => 'Frontend work'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects?search=API');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function projects_are_sorted_alphabetically_by_name()
    {
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Zebra Project']);
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha Project']);
        Project::factory()->create(['user_id' => $this->user->id, 'name' => 'Beta Project']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('Alpha Project', $data[0]['name']);
        $this->assertEquals('Beta Project', $data[1]['name']);
        $this->assertEquals('Zebra Project', $data[2]['name']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_projects()
    {
        $response = $this->getJson('/api/projects');
        $response->assertStatus(401);
    }

    #[Test]
    public function user_only_sees_their_own_projects()
    {
        $otherUser = User::factory()->create();

        Project::factory()->count(5)->create(['user_id' => $this->user->id]);
        Project::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        foreach ($response->json('data') as $project) {
            $this->assertEquals($this->user->id, $project['user_id']);
        }
    }

    #[Test]
    public function empty_search_returns_all_projects()
    {
        Project::factory()->count(4)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects?search=');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    #[Test]
    public function search_is_case_insensitive()
    {
        Project::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Mobile App Development',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects?search=MOBILE');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/projects?search=mobile');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
