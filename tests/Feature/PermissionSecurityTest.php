<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSecurityTest extends TestCase
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

    public function test_non_super_admins_are_blocked_from_all_permission_management_routes()
    {
        // 1. Index route
        $this->actingAs($this->estimator)
            ->get(route('permissions.index'))
            ->assertStatus(403);

        $this->actingAs($this->estimatorAdmin)
            ->get(route('permissions.index'))
            ->assertStatus(403);

        // 2. Edit route
        $this->actingAs($this->estimator)
            ->get(route('permissions.edit', 'estimator_admin'))
            ->assertStatus(403);

        $this->actingAs($this->estimatorAdmin)
            ->get(route('permissions.edit', 'estimator_admin'))
            ->assertStatus(403);

        // 3. Update route
        $this->actingAs($this->estimator)
            ->put(route('permissions.update', 'estimator_admin'), ['permissions' => []])
            ->assertStatus(403);

        $this->actingAs($this->estimatorAdmin)
            ->put(route('permissions.update', 'estimator_admin'), ['permissions' => []])
            ->assertStatus(403);
    }

    public function test_super_admin_can_access_index_and_edit_other_roles()
    {
        $this->actingAs($this->superAdmin)
            ->get(route('permissions.index'))
            ->assertStatus(200);

        $this->actingAs($this->superAdmin)
            ->get(route('permissions.edit', 'estimator_admin'))
            ->assertStatus(200);

        $this->actingAs($this->superAdmin)
            ->put(route('permissions.update', 'estimator_admin'), [
                'permissions' => ['create_estimates', 'edit_estimates']
            ])
            ->assertRedirect(route('permissions.index'));

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'estimator_admin',
            'permission' => 'create_estimates'
        ]);

        $this->assertDatabaseHas('role_permissions', [
            'role' => 'estimator_admin',
            'permission' => 'edit_estimates'
        ]);
    }

    public function test_super_admin_cannot_edit_super_admin_role()
    {
        // 1. Block edit page
        $this->actingAs($this->superAdmin)
            ->get(route('permissions.edit', 'super_admin'))
            ->assertStatus(403);

        // 2. Block update action
        $this->actingAs($this->superAdmin)
            ->put(route('permissions.update', 'super_admin'), [
                'permissions' => ['manage_users']
            ])
            ->assertStatus(403);
    }

    public function test_super_admin_always_passes_has_permission_checks()
    {
        // Delete all super_admin permissions from database
        RolePermission::where('role', 'super_admin')->delete();
        \App\Services\PermissionService::clearCache('super_admin');

        $this->assertDatabaseMissing('role_permissions', [
            'role' => 'super_admin'
        ]);

        // Still passes hasPermission check thanks to the User model bypass
        $this->assertTrue($this->superAdmin->hasPermission('manage_users'));
        $this->assertTrue($this->superAdmin->hasPermission('manage_settings'));
        $this->assertTrue($this->superAdmin->hasPermission('any_random_permission'));
    }
}
