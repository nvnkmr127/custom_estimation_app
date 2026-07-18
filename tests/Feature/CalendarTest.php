<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reminder;
use App\Models\Estimate;
use App\Models\Task;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    protected $estimatorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estimatorUser = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);
    }

    /** @test */
    public function estimator_user_can_access_calendar_page()
    {
        $response = $this->actingAs($this->estimatorUser)
            ->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Estimator Calendar');
    }

    /** @test */
    public function calendar_loads_days_and_groups_user_reminders_correctly()
    {
        $reminder = Reminder::create([
            'user_id' => $this->estimatorUser->id,
            'title' => 'Follow up meeting scheduled',
            'remindable_type' => \App\Models\User::class,
            'remindable_id' => $this->estimatorUser->id,
            'remind_at' => now()->startOfMonth()->addDays(5)->setHour(10)->setMinute(0),
            'type' => 'in_app',
            'is_sent' => false,
        ]);

        Livewire::actingAs($this->estimatorUser)
            ->test(\App\Livewire\Calendar\CalendarIndex::class)
            ->assertSee('Follow up meeting scheduled')
            ->assertSee($reminder->remind_at->format('H:i'));
    }

    /** @test */
    public function calendar_retrieves_estimate_expiries_and_task_deadlines()
    {
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'created_by' => $this->estimatorUser->id,
            'expiry_date' => now()->startOfMonth()->addDays(10),
            'estimate_status' => 'sent',
        ]);

        $task = Task::create([
            'title' => 'Submit design draft',
            'assigned_to' => $this->estimatorUser->id,
            'created_by' => $this->estimatorUser->id,
            'due_date' => now()->startOfMonth()->addDays(12),
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->estimatorUser)
            ->test(\App\Livewire\Calendar\CalendarIndex::class)
            ->assertSee('Expiry: EST #' . $estimate->estimate_number)
            ->assertSee('Task: Submit design draft');
    }

    /** @test */
    public function estimators_can_navigate_between_months()
    {
        $currentDate = Carbon::now();
        $nextMonthDate = $currentDate->copy()->addMonth();
        $prevMonthDate = $currentDate->copy()->subMonth();

        Livewire::actingAs($this->estimatorUser)
            ->test(\App\Livewire\Calendar\CalendarIndex::class)
            ->assertSet('month', $currentDate->month)
            ->call('nextMonth')
            ->assertSet('month', $nextMonthDate->month)
            ->call('previousMonth')
            ->call('previousMonth')
            ->assertSet('month', $prevMonthDate->month);
    }

    /** @test */
    public function estimators_can_create_new_reminders_from_calendar_handler()
    {
        // Must be a future date: saveReminder() correctly rejects reminders set in the past.
        $targetDate = now()->addDays(15)->toDateString();

        Livewire::actingAs($this->estimatorUser)
            ->test(\App\Livewire\Calendar\CalendarIndex::class)
            ->call('openCreateReminder', $targetDate)
            ->set('newReminderTitle', 'Inspect Site Scope')
            ->set('newReminderDescription', 'Verify items on site')
            ->set('newReminderTime', '14:30')
            ->set('newReminderType', 'both')
            ->call('saveReminder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('reminders', [
            'user_id' => $this->estimatorUser->id,
            'title' => 'Inspect Site Scope',
            'description' => 'Verify items on site',
            'type' => 'both',
        ]);
    }
}
