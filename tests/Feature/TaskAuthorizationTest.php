<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function task(User $creator, ?User $assignee = null): Task
    {
        return Task::create([
            'title' => 'Private Task',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $creator->id,
            'assigned_to' => $assignee?->id,
        ]);
    }

    public function test_stranger_cannot_view_update_delete_or_complete_a_task()
    {
        $owner = User::factory()->create(['role' => 'estimator']);
        $stranger = User::factory()->create(['role' => 'estimator']);
        $task = $this->task($owner);

        $this->actingAs($stranger)->get(route('tasks.show', $task))->assertForbidden();
        $this->actingAs($stranger)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($stranger)->put(route('tasks.update', $task), [
            'title' => 'Hacked', 'priority' => 'low', 'status' => 'completed',
        ])->assertForbidden();
        $this->actingAs($stranger)->post(route('tasks.complete', $task))->assertForbidden();
        $this->actingAs($stranger)->delete(route('tasks.destroy', $task))->assertForbidden();

        // Nothing changed
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Private Task', 'status' => 'pending']);
    }

    public function test_creator_assignee_and_admin_can_access_a_task()
    {
        $owner = User::factory()->create(['role' => 'estimator']);
        $assignee = User::factory()->create(['role' => 'estimator']);
        $admin = User::factory()->create(['role' => 'super_admin']);
        $task = $this->task($owner, $assignee);

        $this->actingAs($owner)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($assignee)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($admin)->get(route('tasks.show', $task))->assertOk();

        $this->actingAs($assignee)->post(route('tasks.complete', $task))->assertRedirect();
        $this->assertEquals('completed', $task->fresh()->status);
    }
}
