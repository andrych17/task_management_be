<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskApiTest extends TestCase
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
    public function authenticated_user_can_get_their_tasks_sorted_by_due_date()
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(5)
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => now()->addDays(1)
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => null
        ]);
        Task::factory()->count(2)->create(); // Other user's tasks

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?sort=due_date');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data',
                    'total'
                ]
            ]);

        // Verify sorting: earliest due date first, nulls last
        $tasks = $response->json('data.data');
        $this->assertNotNull($tasks[0]['due_date']);
        $this->assertNotNull($tasks[1]['due_date']);
        $this->assertNull($tasks[2]['due_date']);
    }

    #[Test]
    public function authenticated_user_can_create_task()
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $taskData = [
            'title' => 'New Task',
            'description' => 'Task description',
            'project_id' => $project->id,
            'tags' => ['urgent', 'backend', 'api'], // Tag names as strings
            'due_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'status' => 'todo'
        ];

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'title', 'description', 'status', 'tags', 'project']
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'user_id' => $this->user->id
        ]);

        // Check tags were created
        $this->assertDatabaseHas('tags', [
            'user_id' => $this->user->id,
            'name' => 'urgent'
        ]);
    }

    #[Test]
    public function authenticated_user_can_get_single_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'title', 'description', 'status']
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'title' => $task->title
                ]
            ]);
    }

    #[Test]
    public function user_cannot_get_other_users_task()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Task not found'
            ]);
    }

    #[Test]
    public function task_creation_requires_title()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', [
                'description' => 'Description without title'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['title']
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ]);
    }

    #[Test]
    public function task_title_must_be_unique_per_user()
    {
        // Create first task
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Unique Task Title'
        ]);

        // Try to create duplicate
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', [
                'title' => 'Unique Task Title'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['title']
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'title' => ['You already have a task with this title']
                ]
            ]);
    }

    #[Test]
    public function different_users_can_have_same_task_title()
    {
        $otherUser = User::factory()->create();
        
        // Create task for other user
        Task::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Same Title'
        ]);

        // Create task for current user with same title
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', [
                'title' => 'Same Title'
            ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function task_can_be_created_without_project()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', [
                'title' => 'Standalone Task',
                'status' => 'todo'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', [
            'title' => 'Standalone Task',
            'project_id' => null
        ]);
    }

    #[Test]
    public function task_status_must_be_valid_enum_value()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/tasks', [
                'title' => 'Task with invalid status',
                'status' => 'invalid-status'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['status']
            ])
            ->assertJson([
                'success' => false,
                'errors' => [
                    'status' => ['Status must be one of: todo, in-progress, done']
                ]
            ]);
    }

    #[Test]
    public function task_update_validates_unique_title()
    {
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'First Task'
        ]);
        
        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Second Task'
        ]);

        // Try to update task2 with task1's title
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/tasks/{$task2->id}", [
                'title' => 'First Task'
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'errors' => [
                    'title' => ['You already have a task with this title']
                ]
            ]);
    }

    #[Test]
    public function authenticated_user_can_update_their_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Task Title',
                'status' => 'in-progress'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Updated Task Title',
                    'status' => 'in-progress'
                ]
            ]);
    }

    #[Test]
    public function user_can_update_task_tags()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/tasks/{$task->id}", [
                'tags' => ['frontend', 'react', 'ui'] // Tag names as strings
            ]);

        $response->assertStatus(200);
        $this->assertEquals(3, $task->fresh()->tags->count());

        // Verify tags were created
        $this->assertDatabaseHas('tags', [
            'user_id' => $this->user->id,
            'name' => 'frontend'
        ]);
    }

    #[Test]
    public function user_cannot_update_other_users_task()
    {
        $otherTask = Task::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/tasks/{$otherTask->id}", [
                'title' => 'Hacked Title'
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function authenticated_user_can_delete_their_task()
    {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function user_cannot_delete_other_users_task()
    {
        $otherTask = Task::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/tasks/{$otherTask->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('tasks', ['id' => $otherTask->id]);
    }

    #[Test]
    public function tasks_can_be_paginated()
    {
        Task::factory()->count(30)->create(['user_id' => $this->user->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?per_page=15&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data.data');

        $this->assertEquals(2, $response->json('data.current_page'));
        $this->assertEquals(30, $response->json('data.total'));
    }

    #[Test]
    public function tasks_can_be_filtered_by_search()
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Write documentation',
            'description' => 'API docs'
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Fix bugs',
            'description' => 'Critical issues'
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Update API',
            'description' => 'New endpoints'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?search=documentation');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?search=API');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.data');
    }

    #[Test]
    public function tasks_can_be_filtered_by_status()
    {
        Task::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'todo'
        ]);

        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'in-progress'
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'done'
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?status=todo');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data.data');

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?status=in-progress');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    #[Test]
    public function tasks_can_be_filtered_by_project_id()
    {
        $project1 = Project::factory()->create(['user_id' => $this->user->id]);
        $project2 = Project::factory()->create(['user_id' => $this->user->id]);

        Task::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'project_id' => $project1->id
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'project_id' => $project2->id
        ]);

        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'project_id' => null
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/tasks?project_id={$project1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data.data');
    }

    #[Test]
    public function tasks_can_be_sorted_by_due_date()
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task 1',
            'due_date' => now()->addDays(5)
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task 2',
            'due_date' => now()->addDays(1)
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task 3',
            'due_date' => now()->addDays(10)
        ]);

        // Ascending
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?sort=due_date');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertEquals('Task 2', $data[0]['title']);

        // Descending
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?sort=-due_date');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertEquals('Task 3', $data[0]['title']);
    }

    #[Test]
    public function tasks_can_be_sorted_by_title()
    {
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Zebra task']);
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Alpha task']);
        Task::factory()->create(['user_id' => $this->user->id, 'title' => 'Beta task']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?sort=title');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertEquals('Alpha task', $data[0]['title']);
        $this->assertEquals('Beta task', $data[1]['title']);
        $this->assertEquals('Zebra task', $data[2]['title']);
    }

    #[Test]
    public function combined_filters_work_together()
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Urgent API fix',
            'status' => 'todo',
            'project_id' => $project->id
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Regular API update',
            'status' => 'done',
            'project_id' => $project->id
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Fix bug in API',
            'status' => 'todo',
            'project_id' => null
        ]);

        // Search + Status + Project filter
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson("/api/tasks?search=API&status=todo&project_id={$project->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');

        $this->assertEquals('Urgent API fix', $response->json('data.data.0.title'));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_tasks()
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }

    #[Test]
    public function task_show_returns_404_for_non_existent_task()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Task not found'
            ]);
    }

    #[Test]
    public function task_update_returns_404_for_non_existent_task()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson('/api/tasks/99999', [
                'title' => 'Updated Title'
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function task_delete_returns_404_for_non_existent_task()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function tasks_can_be_filtered_by_single_tag()
    {
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task with urgent tag'
        ]);
        $task1->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'urgent'])->id
        );
        $task1->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'backend'])->id
        );

        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task with feature tag'
        ]);
        $task2->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'feature'])->id
        );

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?tags=urgent');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Task with urgent tag', $data[0]['title']);
    }

    #[Test]
    public function tasks_can_be_filtered_by_multiple_tags()
    {
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task with urgent tag'
        ]);
        $task1->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'urgent'])->id
        );

        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task with feature tag'
        ]);
        $task2->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'feature'])->id
        );

        $task3 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Task with bug tag'
        ]);
        $task3->tags()->attach(
            Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'bug'])->id
        );

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?tags=urgent,feature');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertCount(2, $data);
        $titles = array_column($data, 'title');
        $this->assertContains('Task with urgent tag', $titles);
        $this->assertContains('Task with feature tag', $titles);
    }

    #[Test]
    public function tag_filter_only_returns_user_own_tasks()
    {
        $otherUser = User::factory()->create();

        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'My task'
        ]);
        $urgentTag = Tag::factory()->create(['user_id' => $this->user->id, 'name' => 'urgent']);
        $task1->tags()->attach($urgentTag->id);

        $task2 = Task::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other user task'
        ]);
        $otherUrgentTag = Tag::factory()->create(['user_id' => $otherUser->id, 'name' => 'urgent']);
        $task2->tags()->attach($otherUrgentTag->id);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/tasks?tags=urgent');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('My task', $data[0]['title']);
    }    #[Test]
    public function dashboard_returns_task_counts_by_status()
    {
        // Create tasks with different statuses
        Task::factory()->count(5)->create(['user_id' => $this->user->id, 'status' => 'todo']);
        Task::factory()->count(3)->create(['user_id' => $this->user->id, 'status' => 'in-progress']);
        Task::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'done']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'total_tasks' => 10,
                'todo' => 5,
                'in_progress' => 3,
                'done' => 2
            ]);
    }

    #[Test]
    public function dashboard_returns_zero_counts_when_no_tasks()
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'total_tasks' => 0,
                'todo' => 0,
                'in_progress' => 0,
                'done' => 0
            ]);
    }

    #[Test]
    public function dashboard_only_counts_authenticated_user_tasks()
    {
        $otherUser = User::factory()->create();

        // Create tasks for current user
        Task::factory()->count(3)->create(['user_id' => $this->user->id, 'status' => 'todo']);

        // Create tasks for other user (should not be counted)
        Task::factory()->count(5)->create(['user_id' => $otherUser->id, 'status' => 'todo']);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'total_tasks' => 3,
                'todo' => 3,
                'in_progress' => 0,
                'done' => 0
            ]);
    }
}
