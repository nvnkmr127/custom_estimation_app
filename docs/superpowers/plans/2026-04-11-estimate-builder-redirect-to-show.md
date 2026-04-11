# Estimate Builder Redirect-to-Show Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** After Create/Update (and Save as New Version) in the estimate builder, navigate to the estimate show page; keep the plain “Save” action staying on the builder.

**Architecture:** The Alpine builder already redirects only for “Create/Update/Save as New Version” via `result.redirect_url`. We will change the backend JSON contract so `redirect_url` points to `estimates.show` instead of `estimates.edit`.

**Tech Stack:** Laravel (controllers, FormRequests), PHPUnit feature tests, Alpine.js (already wired).

---

## File Map

**Modify**
- [EstimateController.php](file:///Users/naveenadicharla/Documents/custom_estimation_app/app/Http/Controllers/EstimateController.php) (JSON responses in `store()` + `update()`)

**Create**
- `tests/Feature/EstimateBuilderRedirectTest.php` (verifies JSON `redirect_url` points to show for store/update)

---

### Task 1: Make `store()` JSON response redirect to show

**Files:**
- Modify: `app/Http/Controllers/EstimateController.php` (`store()` JSON branch)
- Test: `tests/Feature/EstimateBuilderRedirectTest.php`

- [ ] **Step 1: Write failing test for create JSON redirect**

Create `tests/Feature/EstimateBuilderRedirectTest.php` with:

```php
<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateBuilderRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function store_json_returns_redirect_url_to_show()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $response = $this->actingAs($user)->postJson(route('estimates.store'), [
            'client_id' => $client->id,
            'estimate_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addDays(7)->format('Y-m-d'),
            'status' => 'draft',
            'currency' => 'USD',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Line Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'unit_type' => 'nos',
                    'tax_1' => 0,
                    'tax_2' => 0,
                    'order_index' => 0,
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $estimateId = $response->json('estimate_id');
        $this->assertNotNull($estimateId);

        $response->assertJson([
            'redirect_url' => route('estimates.show', $estimateId),
        ]);
    }
}
```

- [ ] **Step 2: Run the test to confirm it fails**

Run:

```bash
php artisan test --filter=EstimateBuilderRedirectTest::store_json_returns_redirect_url_to_show
```

Expected: FAIL because `redirect_url` currently points to `estimates.edit`.

- [ ] **Step 3: Update the controller JSON response**

In `store()` JSON branch, change:

```php
'redirect_url' => route('estimates.edit', $estimate),
```

to:

```php
'redirect_url' => route('estimates.show', $estimate),
```

- [ ] **Step 4: Re-run the test**

Run:

```bash
php artisan test --filter=EstimateBuilderRedirectTest::store_json_returns_redirect_url_to_show
```

Expected: PASS.

---

### Task 2: Make `update()` JSON response redirect to show (including branched versions)

**Files:**
- Modify: `app/Http/Controllers/EstimateController.php` (`update()` JSON branch)
- Test: `tests/Feature/EstimateBuilderRedirectTest.php`

- [ ] **Step 1: Add failing test for update JSON redirect**

Append to `EstimateBuilderRedirectTest.php`:

```php
/** @test */
public function update_json_returns_redirect_url_to_show()
{
    $user = User::factory()->create(['role' => 'super_admin']);
    $client = Client::factory()->create();

    $estimate = \App\Models\Estimate::factory()->create([
        'created_by' => $user->id,
        'client_id' => $client->id,
        'estimate_status' => \App\Models\Estimate::EST_STATUS_DRAFT,
        'is_current_version' => true,
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($user)->putJson(route('estimates.update', $estimate), [
        'title' => 'Updated Title',
        'client_id' => $client->id,
        'estimate_date' => now()->format('Y-m-d'),
        'expiry_date' => now()->addDays(7)->format('Y-m-d'),
        'status' => \App\Models\Estimate::EST_STATUS_DRAFT,
        'currency' => 'USD',
        'discount_type' => 'percentage',
        'discount_value' => 0,
        'type' => 'standard',
        'items' => [
            [
                'name' => 'Line Item',
                'quantity' => 1,
                'unit_price' => 100,
                'unit_type' => 'nos',
                'tax_1' => 0,
                'tax_2' => 0,
                'order_index' => 0,
            ],
        ],
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $updatedId = $response->json('estimate_id');
    $this->assertNotNull($updatedId);

    $response->assertJson([
        'redirect_url' => route('estimates.show', $updatedId),
    ]);
}
```

- [ ] **Step 2: Run the update test to confirm it fails**

Run:

```bash
php artisan test --filter=EstimateBuilderRedirectTest::update_json_returns_redirect_url_to_show
```

Expected: FAIL because `redirect_url` currently points to `estimates.edit`.

- [ ] **Step 3: Update the controller JSON response**

In `update()` JSON branch, change:

```php
'redirect_url' => route('estimates.edit', $updatedEstimate),
```

to:

```php
'redirect_url' => route('estimates.show', $updatedEstimate),
```

- [ ] **Step 4: Re-run the update test**

Run:

```bash
php artisan test --filter=EstimateBuilderRedirectTest::update_json_returns_redirect_url_to_show
```

Expected: PASS.

---

### Task 3: Run full suite for regression safety

**Files:**
- None (verification only)

- [ ] **Step 1: Run full tests**

Run:

```bash
php artisan test
```

Expected: PASS.

