<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\Automation;
use App\Models\Client;
use App\Models\CouponCode;
use App\Models\EmailTemplate;
use App\Models\Estimate;
use App\Models\ItemPackage;
use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RoomTemplate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSmokeParamTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_and_edit_routes_do_not_500()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);

        $seedErrors = [];
        $seed = function (string $label, callable $fn) use (&$seedErrors) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $seedErrors[] = "SEED $label: " . $e->getMessage();
                return null;
            }
        };

        $client = $seed('client', fn () => Client::factory()->create());
        $category = $seed('category', fn () => ProductCategory::factory()->create());
        $product = $seed('product', fn () => Product::factory()->create());
        $pdfTemplate = $seed('pdfTemplate', fn () => PdfTemplate::factory()->create());
        $estimate = $seed('estimate', fn () => Estimate::factory()->create(['created_by' => $admin->id]));
        $targetUser = $seed('user', fn () => User::factory()->create(['role' => 'estimator']));

        $task = $seed('task', fn () => Task::create([
            'title' => 'Smoke Task', 'status' => 'pending', 'priority' => 'medium',
            'created_by' => $admin->id, 'assigned_to' => $admin->id,
        ]));
        $coupon = $seed('coupon', fn () => CouponCode::create([
            'code' => 'SMOKE10', 'name' => 'Smoke', 'type' => 'fixed', 'value' => 10, 'is_active' => true,
        ]));
        $package = $seed('package', fn () => ItemPackage::create([
            'name' => 'Smoke Pack', 'total_price' => 100, 'items' => [],
        ]));
        $emailTemplate = $seed('emailTemplate', fn () => EmailTemplate::create([
            'code' => 'smoke', 'event_trigger' => 'manual', 'name' => 'Smoke', 'subject' => 'Hi',
            'body_html' => '<p>Hi</p>',
        ]));
        $chain = $seed('approvalChain', fn () => ApprovalChain::create([
            'name' => 'Smoke Chain', 'is_active' => true,
        ]));
        $roomTemplate = $seed('roomTemplate', fn () => RoomTemplate::create([
            'name' => 'Smoke Room', 'items' => [], 'allowed_unit_types' => [],
        ]));
        $automation = $seed('automation', fn () => Automation::create([
            'name' => 'Smoke Automation', 'is_active' => true, 'version' => 1,
            'is_current_version' => true, 'created_by' => $admin->id,
        ]));

        // route name => model instance (null-safe: skipped if seeding failed)
        $routes = [];
        $add = function (string $name, $model, $extra = []) use (&$routes) {
            if ($model) $routes[] = [$name, $model, $extra];
        };

        $add('clients.show', $client);
        $add('clients.edit', $client);
        $add('categories.show', $category);
        $add('categories.edit', $category);
        $add('products.edit', $product);
        $add('pdf-templates.edit', $pdfTemplate);
        $add('estimates.show', $estimate);
        $add('estimates.edit', $estimate);
        $add('estimates.analytics', $estimate);
        $add('estimates.print', $estimate);
        $add('comments.index', $estimate);
        $add('users.show', $targetUser);
        $add('users.edit', $targetUser);
        $add('tasks.show', $task);
        $add('tasks.edit', $task);
        $add('coupons.show', $coupon);
        $add('coupons.edit', $coupon);
        $add('packages.show', $package);
        $add('packages.edit', $package);
        $add('email-templates.show', $emailTemplate);
        $add('approval-chains.show', $chain);
        $add('templates.show', $roomTemplate);
        $add('templates.edit', $roomTemplate);
        $add('automation.edit', $automation);
        $add('automation.logs', $automation);
        $add('automation.metrics', $automation);
        $add('automation.timeline', $automation);
        $add('automation.flowchart', $automation);

        $failures = [];
        foreach ($routes as [$name, $model, $extra]) {
            try {
                $response = $this->get(route($name, array_merge([$model], $extra)));
                $status = $response->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "$name => $status";
                }
            } catch (\Throwable $e) {
                $failures[] = "$name => EXCEPTION: " . $e->getMessage();
            }
        }

        // permissions.edit takes a role name string, not a model
        try {
            $status = $this->get('/permissions/estimator/edit')->getStatusCode();
            if ($status >= 500) $failures[] = "permissions.edit => $status";
        } catch (\Throwable $e) {
            $failures[] = "permissions.edit => EXCEPTION: " . $e->getMessage();
        }

        $this->assertEmpty(
            array_merge($seedErrors, $failures),
            "Issues found:\n" . implode("\n", array_merge($seedErrors, $failures))
        );
    }
}
