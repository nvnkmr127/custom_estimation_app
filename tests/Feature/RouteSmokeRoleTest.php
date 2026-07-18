<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeRoleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A non-admin estimator should never hit a 500 on any page route.
     * 403/302 (gated) are correct and ignored; only >=500 is a bug.
     */
    public function test_page_routes_do_not_500_for_estimator()
    {
        $estimator = User::factory()->create(['role' => 'estimator']);

        $routes = [
            '/', 'dashboard', 'activities', 'admin/plugins', 'admin/webhook-catchers',
            'admin/webhook-dlq', 'admin/webhook-events', 'admin/webhook-logs', 'admin/webhooks',
            'admin/webhooks/create', 'approval-chains', 'approval-chains/create',
            'approval-checklists', 'approval-checklists/create', 'approvals', 'automation',
            'automation/analytics/dashboard', 'automation/create', 'automation/experiments',
            'automation/experiments/create', 'backup', 'brands', 'brands/create', 'calendar',
            'categories', 'categories/create', 'change-request-checklists',
            'change-request-checklists/create', 'clients', 'clients/create', 'coupons',
            'coupons/create', 'email-templates', 'email-templates/create', 'estimates',
            'estimates/create', 'event-logs', 'notifications', 'packages', 'packages/create',
            'pdf-templates', 'pdf-templates/create', 'permissions', 'products', 'products/create',
            'products/pending/list', 'profile', 'reminders', 'reports', 'search', 'settings',
            'settings/api-portal', 'settings/nurture', 'tasks', 'tasks/create', 'templates',
            'templates/create', 'unit-types', 'user-guide', 'users', 'users/create', 'users/trash',
        ];

        $failures = [];
        foreach ($routes as $route) {
            try {
                $status = $this->actingAs($estimator)->get('/' . ltrim($route, '/'))->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "$route => $status";
                }
            } catch (\Throwable $e) {
                $failures[] = "$route => EXCEPTION: " . $e->getMessage();
            }
        }

        $this->assertEmpty($failures, "Routes returning 500 for estimator:\n" . implode("\n", $failures));
    }
}
