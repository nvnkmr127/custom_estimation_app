<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $estimatorAdmin;
    private User $estimator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->estimatorAdmin = User::factory()->create(['role' => 'estimator_admin']);
        $this->estimator = User::factory()->create(['role' => 'estimator']);
    }

    public function test_non_super_admins_are_blocked_from_all_user_management_routes()
    {
        $targetUser = User::factory()->create(['role' => 'estimator']);

        // 1. Index route
        $this->actingAs($this->estimator)
            ->get(route('users.index'))
            ->assertStatus(403);

        // 2. Create route
        $this->actingAs($this->estimator)
            ->get(route('users.create'))
            ->assertStatus(403);

        // 3. Store route
        $this->actingAs($this->estimator)
            ->post(route('users.store'), [])
            ->assertStatus(403);

        // 4. Show route
        $this->actingAs($this->estimator)
            ->get(route('users.show', $targetUser))
            ->assertStatus(403);

        // 5. Edit route
        $this->actingAs($this->estimator)
            ->get(route('users.edit', $targetUser))
            ->assertStatus(403);

        // 6. Update route
        $this->actingAs($this->estimator)
            ->put(route('users.update', $targetUser), [])
            ->assertStatus(403);

        // 7. Destroy route
        $this->actingAs($this->estimator)
            ->delete(route('users.destroy', $targetUser))
            ->assertStatus(403);

        // 8. Trash route
        $this->actingAs($this->estimator)
            ->get(route('users.trash'))
            ->assertStatus(403);

        // 9. Restore route
        $this->actingAs($this->estimator)
            ->post(route('users.restore', $targetUser->id))
            ->assertStatus(403);
    }

    public function test_super_admin_can_perform_user_crud_actions()
    {
        $targetUser = User::factory()->create(['role' => 'estimator', 'name' => 'John Doe', 'email' => 'john@example.com']);

        // Index
        $this->actingAs($this->superAdmin)
            ->get(route('users.index'))
            ->assertStatus(200);

        // Show
        $this->actingAs($this->superAdmin)
            ->get(route('users.show', $targetUser))
            ->assertStatus(200);

        // Edit
        $this->actingAs($this->superAdmin)
            ->get(route('users.edit', $targetUser))
            ->assertStatus(200);

        // Update
        $this->actingAs($this->superAdmin)
            ->put(route('users.update', $targetUser), [
                'name' => 'John Updated',
                'email' => 'john.updated@example.com',
                'role' => 'estimator_manager',
                'max_discount_percentage' => 15,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'John Updated',
            'role' => 'estimator_manager',
        ]);

        // Destroy
        $this->actingAs($this->superAdmin)
            ->delete(route('users.destroy', $targetUser))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);

        // Restore
        $this->actingAs($this->superAdmin)
            ->post(route('users.restore', $targetUser->id))
            ->assertRedirect(route('users.index'));

        $this->assertNotSoftDeleted('users', ['id' => $targetUser->id]);
    }

    public function test_last_super_admin_cannot_demote_themselves()
    {
        // There is only one super_admin in the system (the active one)
        $this->assertCount(1, User::where('role', 'super_admin')->get());

        // Attempting self-demotion
        $response = $this->actingAs($this->superAdmin)
            ->from(route('users.edit', $this->superAdmin))
            ->put(route('users.update', $this->superAdmin), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'role' => 'estimator', // Changing role to estimator
                'max_discount_percentage' => 0,
            ]);

        $response->assertRedirect(route('users.edit', $this->superAdmin));
        $response->assertSessionHas('error', 'You cannot demote yourself because you are the only Super Admin in the system.');

        // Role remains super_admin
        $this->superAdmin->refresh();
        $this->assertEquals('super_admin', $this->superAdmin->role);
    }

    public function test_super_admin_can_demote_themselves_if_another_super_admin_exists()
    {
        // Create another super admin
        $anotherAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->assertCount(2, User::where('role', 'super_admin')->get());

        // Attempting self-demotion
        $response = $this->actingAs($this->superAdmin)
            ->put(route('users.update', $this->superAdmin), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'role' => 'estimator', // Changing role to estimator
                'max_discount_percentage' => 0,
            ]);

        $response->assertRedirect(route('users.index'));

        // Role is successfully changed
        $this->superAdmin->refresh();
        $this->assertEquals('estimator', $this->superAdmin->role);
    }
}
