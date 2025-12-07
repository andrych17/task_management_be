<?php

namespace App\Repositories;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks for a user, sorted by due date
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllByUser(int $userId): Collection
    {
        return Task::where('user_id', $userId)
            ->with(['project', 'tags'])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function findById(int $id): ?Task
    {
        return Task::with(['project', 'tags'])->find($id);
    }

    /**
     * Create a new task
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task
    {
        $tagNames = $data['tags'] ?? [];
        unset($data['tags']);

        $task = Task::create($data);

        if (!empty($tagNames)) {
            $this->syncTagsByName($task->id, $tagNames);
        }

        return $task->load(['project', 'tags']);
    }

    /**
     * Update a task
     *
     * @param int $id
     * @param array $data
     * @return Task
     */
    public function update(int $id, array $data): Task
    {
        $task = Task::findOrFail($id);

        $tagNames = $data['tags'] ?? null;
        unset($data['tags']);

        $task->update($data);

        if ($tagNames !== null) {
            $this->syncTagsByName($task->id, $tagNames);
        }

        return $task->load(['project', 'tags']);
    }

    /**
     * Delete a task
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $task = Task::findOrFail($id);
        return $task->delete();
    }

    /**
     * Sync tags for a task by tag IDs
     *
     * @param int $taskId
     * @param array $tagIds
     * @return void
     */
    public function syncTags(int $taskId, array $tagIds): void
    {
        $task = Task::findOrFail($taskId);
        $task->tags()->sync($tagIds);
    }

    /**
     * Sync tags for a task by tag names (create if not exists)
     *
     * @param int $taskId
     * @param array $tagNames
     * @return void
     */
    public function syncTagsByName(int $taskId, array $tagNames): void
    {
        $task = Task::findOrFail($taskId);
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            // Find or create tag for this user
            $tag = \App\Models\Tag::firstOrCreate(
                [
                    'user_id' => $task->user_id,
                    'name' => trim($tagName)
                ]
            );
            $tagIds[] = $tag->id;
        }

        $task->tags()->sync($tagIds);
    }
}
