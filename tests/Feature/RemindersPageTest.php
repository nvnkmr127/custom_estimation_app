<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemindersPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function reminders_index_shows_create_form()
    {
        $user = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertOk();
        $response->assertSee('Add Reminder');
        $response->assertSee('name="remindable_type"', false);
        $response->assertSee('name="remindable_id"', false);
    }
}
