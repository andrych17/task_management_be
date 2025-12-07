<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks for a user, sorted by due date
     *
     * @param int $userId
     * @return Collection
     */
    public function getAllByUser(int $userId): Collection;

    /**
     * Find a task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function findById(int $id): ?Task;

    /**
     * Create a new task
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task;

    /**
     * Update a task
     *
     * @param int $id
     * @param array $data
     * @return Task
     */
    public function update(int $id, array $data): Task;

    /**
     * Delete a task
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Sync tags for a task
     *
     * @param int $taskId
     * @param array $tagIds
     * @return void
     */
    public function syncTags(int $taskId, array $tagIds): void;
}
