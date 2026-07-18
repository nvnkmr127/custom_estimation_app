<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\Client;
use App\Models\CouponCode;
use App\Models\EmailTemplate;
use App\Models\ItemPackage;
use App\Models\ProductCategory;
use App\Models\RoomTemplate;
use App\Models\Task;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Exercise store() (create) and destroy() with valid payloads.
     * A valid create must not 500 and must not fail validation (no 422),
     * so it should redirect (3xx) or return 2xx. destroy() must not 500.
     */
    public function test_create_and_delete_paths()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);

        // route => [valid payload, Model class for cleanup delete]
        $cases = [
            'clients.store' => [['name' => 'Smoke Client'], Client::class, 'clients.destroy'],
            'tasks.store' => [['title' => 'Smoke Task', 'priority' => 'medium', 'status' => 'pending'], Task::class, 'tasks.destroy'],
            'coupons.store' => [['code' => 'SMOKEW1', 'name' => 'Smoke', 'type' => 'percentage', 'value' => 10], CouponCode::class, 'coupons.destroy'],
            'categories.store' => [['name' => 'Smoke Category'], ProductCategory::class, 'categories.destroy'],
            'unit-types.store' => [['name' => 'Smoke Unit', 'units' => ['ea']], UnitType::class, 'unit-types.destroy'],
            'packages.store' => [['name' => 'Smoke Pack', 'items' => [['item_name' => 'X', 'quantity' => 1]]], ItemPackage::class, 'packages.destroy'],
            'templates.store' => [['name' => 'Smoke Room', 'items' => [['item_name' => 'X', 'quantity' => 1]]], RoomTemplate::class, 'templates.destroy'],
            'approval-chains.store' => [['name' => 'Smoke Chain', 'steps' => [['user_id' => null, 'order' => 1]]], ApprovalChain::class, 'approval-chains.destroy'],
            'email-templates.store' => [['code' => 'smokew', 'event_trigger' => 'manual', 'name' => 'Smoke', 'subject' => 'Hi', 'body_html' => '<p>Hi</p>'], EmailTemplate::class, 'email-templates.destroy'],
        ];

        $failures = [];
        foreach ($cases as $route => [$payload, $modelClass, $destroyRoute]) {
            // approval chain step needs a real user id
            if ($route === 'approval-chains.store') {
                $payload['steps'][0]['user_id'] = $admin->id;
            }

            try {
                $before = $modelClass::count();
                $response = $this->post(route($route), $payload);
                $status = $response->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "$route (create) => $status";
                    continue;
                }
                if ($status === 422 || $status === 419) {
                    $failures[] = "$route (create) => $status (validation/csrf rejected a payload that should be valid)";
                    continue;
                }
                if ($modelClass::count() <= $before) {
                    $failures[] = "$route (create) => $status but no record persisted";
                    continue;
                }

                // destroy the record we just made
                $record = $modelClass::latest('id')->first();
                $delStatus = $this->delete(route($destroyRoute, $record))->getStatusCode();
                if ($delStatus >= 500) {
                    $failures[] = "$destroyRoute (delete) => $delStatus";
                }
            } catch (\Throwable $e) {
                $failures[] = "$route => EXCEPTION: " . $e->getMessage();
            }
        }

        $this->assertEmpty($failures, "Write-path issues:\n" . implode("\n", $failures));
    }
}
