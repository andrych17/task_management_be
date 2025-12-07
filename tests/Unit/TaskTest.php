<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->id, $task->user->id);
    }

    #[Test]
    public function it_belongs_to_a_project()
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    #[Test]
    public function it_can_have_no_project()
    {
        $task = Task::factory()->withoutProject()->create();

        $this->assertNull($task->project_id);
        $this->assertNull($task->project);
    }

    #[Test]
    public function it_has_many_tags()
    {
        $task = Task::factory()->create();
        $tags = Tag::factory()->count(3)->create(['user_id' => $task->user_id]);

        $task->tags()->attach($tags);

        $this->assertCount(3, $task->tags);
        $this->assertInstanceOf(Tag::class, $task->tags->first());
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $task = new Task();
        $fillable = ['user_id', 'project_id', 'title', 'description', 'due_date', 'status'];

        $this->assertEquals($fillable, $task->getFillable());
    }

    #[Test]
    public function it_casts_due_date_to_datetime()
    {
        $task = Task::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $task->due_date);
    }

    #[Test]
    public function it_has_valid_status_values()
    {
        $todoTask = Task::factory()->todo()->create();
        $inProgressTask = Task::factory()->inProgress()->create();
        $doneTask = Task::factory()->done()->create();

        $this->assertEquals('todo', $todoTask->status);
        $this->assertEquals('in-progress', $inProgressTask->status);
        $this->assertEquals('done', $doneTask->status);
    }

    #[Test]
    public function it_can_sync_tags()
    {
        $task = Task::factory()->create();
        $tags = Tag::factory()->count(2)->create(['user_id' => $task->user_id]);

        $task->tags()->sync($tags->pluck('id'));

        $this->assertCount(2, $task->tags);

        // Sync with different tags
        $newTags = Tag::factory()->count(3)->create(['user_id' => $task->user_id]);
        $task->tags()->sync($newTags->pluck('id'));

        $this->assertCount(3, $task->fresh()->tags);
    }
}
