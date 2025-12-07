<?php

namespace Tests\Unit;

use App\Repositories\TaskRepository;
use App\Models\Task;
use App\Models\User;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository();
    }

    #[Test]
    public function it_can_get_all_tasks_for_user()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->count(3)->create([
            'user_id' => $user->id,
            'project_id' => $project->id
        ]);

        $tasks = $this->repository->getAllByUser($user->id);

        $this->assertCount(3, $tasks);
    }

    #[Test]
    public function it_can_find_task_by_id()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id
        ]);

        $found = $this->repository->findById($task->id, $user->id);

        $this->assertEquals($task->id, $found->id);
    }

    #[Test]
    public function it_returns_null_when_task_not_found()
    {
        $user = User::factory()->create();

        $found = $this->repository->findById(999, $user->id);

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_create_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $data = [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'New Task',
            'description' => 'Task description',
            'due_date' => now()->addDays(7),
            'status' => 'todo'
        ];

        $task = $this->repository->create($data);

        $this->assertDatabaseHas('tasks', [
            'title' => 'New Task',
            'user_id' => $user->id
        ]);
    }

    #[Test]
    public function it_can_create_task_with_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $data = [
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Task with tags',
            'tags' => ['urgent', 'backend', 'api']
        ];

        $task = $this->repository->create($data);
        $task->load('tags');

        $this->assertCount(3, $task->tags);
        $this->assertEquals('urgent', $task->tags[0]->name);
    }

    #[Test]
    public function it_can_update_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Original Title'
        ]);

        $updated = $this->repository->update($task->id, [
            'title' => 'Updated Title'
        ]);

        $this->assertEquals('Updated Title', $updated->title);
    }

    #[Test]
    public function it_can_update_task_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id
        ]);

        $updated = $this->repository->update($task->id, [
            'tags' => ['frontend', 'react']
        ]);
        $updated->load('tags');

        $this->assertCount(2, $updated->tags);
    }

    #[Test]
    public function it_can_delete_task()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id
        ]);

        $result = $this->repository->delete($task->id, $user->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    #[Test]
    public function it_reuses_existing_tags()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Create a tag first
        $existingTag = Tag::create([
            'user_id' => $user->id,
            'name' => 'urgent'
        ]);

        $task = $this->repository->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Task',
            'tags' => ['urgent', 'backend']
        ]);
        $task->load('tags');

        // Should have 2 tags total
        $this->assertCount(2, $task->tags);

        // Should reuse the existing 'urgent' tag
        $urgentTag = $task->tags->firstWhere('name', 'urgent');
        $this->assertEquals($existingTag->id, $urgentTag->id);

        // Should only have 2 total tags in database for this user
        $this->assertEquals(2, Tag::where('user_id', $user->id)->count());
    }

    #[Test]
    public function it_can_sync_tags_by_tag_ids()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id
        ]);

        $tags = Tag::factory()->count(3)->create(['user_id' => $user->id]);
        $tagIds = $tags->pluck('id')->toArray();

        $this->repository->syncTags($task->id, $tagIds);
        $task->refresh();

        $this->assertCount(3, $task->tags);
        $this->assertEquals($tagIds, $task->tags->pluck('id')->sort()->values()->toArray());
    }
}
