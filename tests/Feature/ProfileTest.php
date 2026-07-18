<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotNull($user->fresh());
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_last_super_admin_cannot_delete_their_own_account(): void
    {
        $onlySuperAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($onlySuperAdmin)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHas('error');

        $this->assertNotNull($onlySuperAdmin->fresh());
        $this->assertFalse($onlySuperAdmin->fresh()->trashed());
    }

    public function test_super_admin_can_delete_own_account_when_another_exists(): void
    {
        User::factory()->create(['role' => 'super_admin']);
        $self = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($self)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertTrue($self->fresh()->trashed());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_sso_user_email_and_name_cannot_be_updated_locally(): void
    {
        $user = User::factory()->create([
            'source' => 'sso',
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Original Name', $user->name);
        $this->assertSame('original@example.com', $user->email);
    }

    public function test_sso_user_can_delete_their_account_without_password(): void
    {
        $user = User::factory()->create([
            'source' => 'sso',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response->assertSessionHasNoErrors()->assertRedirect('/');

        $this->assertGuest();
        $this->assertTrue($user->fresh()->trashed());
    }

    public function test_sso_user_cannot_update_their_password(): void
    {
        $user = User::factory()->create([
            'source' => 'sso',
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertStatus(403);
    }

    public function test_sso_user_deletion_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'source' => 'sso',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response->assertRedirect('/');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user_self_deleted',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }
}
