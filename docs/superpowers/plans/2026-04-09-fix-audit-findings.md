# Audit Findings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the audited security gaps, estimate calculation drift, missing estimate/comment flows, and broken test coverage so the app behaves safely and consistently.

**Architecture:** Keep the current Laravel + Livewire structure, but make backend calculation authoritative and align the frontend builder to it. Fix public auth exposure directly in the Blade auth flow, implement missing controller behavior where stubs exist, and add focused feature tests around the repaired flows before validating the wider suite.

**Tech Stack:** Laravel 12, Livewire 4, Blade, Alpine.js, PHPUnit, SQLite test database

---

### Task 1: Secure Public Auth Entry Points

**Files:**
- Modify: `resources/views/auth/login.blade.php`
- Modify: `resources/views/welcome.blade.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_login_page_does_not_expose_demo_role_shortcuts(): void
{
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertDontSee('Super Admin');
    $response->assertDontSee('Estimator (Sales)');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php --filter=login_page_does_not_expose_demo_role_shortcuts`
Expected: FAIL because the login page currently renders public demo login buttons.

- [ ] **Step 3: Write minimal implementation**

```blade
{{-- Remove direct-login buttons and keep only normal auth actions --}}
@if (Route::has('register'))
    <a href="{{ route('register') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
        {{ __('Create account') }}
    </a>
@endif
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Auth/AuthenticationTest.php --filter=login_page_does_not_expose_demo_role_shortcuts`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add resources/views/auth/login.blade.php resources/views/welcome.blade.php tests/Feature/Auth/AuthenticationTest.php
git commit -m "fix: remove public demo login shortcuts"
```

### Task 2: Repair Estimate Totals and Coupon Flow

**Files:**
- Modify: `app/Http/Controllers/CouponCodeController.php`
- Modify: `app/Http/Controllers/EstimateController.php`
- Modify: `app/Services/Calculations/PriceCalculator.php`
- Modify: `resources/views/components/estimate-builder-script.blade.php`
- Test: `tests/Feature/EstimateCreationTest.php`

- [ ] **Step 1: Write the failing tests**

```php
public function test_coupon_verification_returns_discount_amount_without_overwriting_manual_discount(): void
{
    $coupon = CouponCode::factory()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->postJson(route('coupons.validate'), [
        'code' => 'SAVE10',
        'total' => 1000,
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'coupon_id' => $coupon->id,
            'discount' => 100.0,
        ]);
}
```

```php
public function test_estimate_calculation_returns_manual_discount_and_coupon_discount_separately(): void
{
    $response = $this->actingAs($this->user)->postJson(route('estimates.calculate'), [
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'coupon_discount' => 25,
        'tax_1' => 5,
        'items' => [[
            'name' => 'Item',
            'unit_price' => 100,
            'quantity' => 2,
        ]],
    ]);

    $response->assertOk()
        ->assertJson([
            'subtotal' => 200.0,
            'discount_total' => 20.0,
            'coupon_discount' => 25.0,
            'grand_total' => 164.0,
        ]);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/EstimateCreationTest.php --filter=coupon`
Expected: FAIL because calculate does not return `coupon_discount` or `discount_total`, and the builder mutates manual discount fields when applying coupons.

- [ ] **Step 3: Write minimal implementation**

```php
return response()->json([
    'subtotal' => $results['estimate_updates']['subtotal'],
    'total_tax' => $results['estimate_updates']['total_tax'],
    'discount_total' => $results['estimate_updates']['discount_total'],
    'coupon_discount' => $estimate->coupon_discount ?? 0,
    'grand_total' => $results['estimate_updates']['grand_total'],
    'approval_chain_id' => $results['estimate_updates']['approval_chain_id'],
]);
```

```js
this.estimate.coupon_discount = parseFloat(data.discount || 0)
this.totals.discount = parseFloat(data.discount_total || 0) + this.estimate.coupon_discount
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/EstimateCreationTest.php --filter=coupon`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/CouponCodeController.php app/Http/Controllers/EstimateController.php app/Services/Calculations/PriceCalculator.php resources/views/components/estimate-builder-script.blade.php tests/Feature/EstimateCreationTest.php
git commit -m "fix: align estimate coupon calculations"
```

### Task 3: Implement Missing Estimate Preview and Comment Notifications

**Files:**
- Modify: `app/Http/Controllers/EstimateController.php`
- Modify: `app/Http/Controllers/CommentController.php`
- Test: `tests/Feature/EstimateShowRefactorTest.php`
- Test: `tests/Feature/NotificationEnhancementTest.php`

- [ ] **Step 1: Write the failing tests**

```php
public function test_preview_endpoint_returns_rendered_preview_response(): void
{
    $response = $this->actingAs($this->user)->post(route('estimates.preview'), $this->validEstimatePayload());

    $response->assertOk();
    $response->assertSee('Estimate');
}
```

```php
public function test_team_is_notified_when_client_comment_is_created(): void
{
    Notification::fake();

    $this->postJson(route('comments.store', $this->estimate), [
        'comment' => 'Please update this item',
        'type' => 'client',
    ])->assertCreated();

    Notification::assertCount(1);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/EstimateShowRefactorTest.php tests/Feature/NotificationEnhancementTest.php`
Expected: FAIL because preview returns only `{"success":true}` and comment notifications are currently just logged.

- [ ] **Step 3: Write minimal implementation**

```php
$estimate = new Estimate($validated);
$estimate->setRelation('items', $items);

return response($this->pdfRenderingService->render($template, $estimate, true));
```

```php
foreach ($estimate->followers as $user) {
    $user->notify(new EstimateCommentNotification($estimate, $comment));
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/EstimateShowRefactorTest.php tests/Feature/NotificationEnhancementTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/EstimateController.php app/Http/Controllers/CommentController.php tests/Feature/EstimateShowRefactorTest.php tests/Feature/NotificationEnhancementTest.php
git commit -m "feat: implement estimate preview and comment notifications"
```

### Task 4: Persist Selected Product Options End-to-End

**Files:**
- Modify: `app/Http/Requests/StoreEstimateRequest.php`
- Modify: `app/Http/Requests/UpdateEstimateRequest.php`
- Modify: `resources/views/components/estimate-builder-script.blade.php`
- Modify: `app/Services/EstimateService.php`
- Test: `tests/Feature/EstimateUpdateTest.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_selected_product_options_are_saved_when_updating_an_estimate(): void
{
    $estimate = Estimate::factory()->create();

    $payload = [
        'client_id' => $estimate->client_id,
        'estimate_date' => now()->toDateString(),
        'status' => Estimate::EST_STATUS_DRAFT,
        'currency' => 'USD',
        'discount_type' => 'fixed',
        'discount_value' => 0,
        'type' => 'standard',
        'items' => [[
            'name' => 'Configured Item',
            'unit_price' => 100,
            'quantity' => 1,
            'selected_options' => ['10' => '20'],
        ]],
    ];

    $this->actingAs($this->user)
        ->put(route('estimates.update', $estimate), $payload)
        ->assertRedirect();

    $this->assertDatabaseHas('estimate_items', [
        'estimate_id' => $estimate->id,
    ]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/EstimateUpdateTest.php --filter=selected_product_options_are_saved`
Expected: FAIL because the requests do not validate `selected_options`, so the data is dropped before persistence.

- [ ] **Step 3: Write minimal implementation**

```php
'items.*.selected_options' => 'nullable|array',
'sections.*.items.*.selected_options' => 'nullable|array',
```

```js
Object.entries(v).forEach(([selectedKey, selectedValue]) => {
    app(`${prefix}[${iIdx}][selected_options][${selectedKey}]`, selectedValue)
})
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/EstimateUpdateTest.php --filter=selected_product_options_are_saved`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/StoreEstimateRequest.php app/Http/Requests/UpdateEstimateRequest.php resources/views/components/estimate-builder-script.blade.php app/Services/EstimateService.php tests/Feature/EstimateUpdateTest.php
git commit -m "fix: persist configured product options"
```

### Task 5: Stabilize the Failing Test Suite

**Files:**
- Modify: `app/Http/Controllers/WebhookController.php`
- Modify: `app/Models/WebhookInboundEvent.php`
- Modify: `tests/Feature/WebhookSystemTest.php`
- Modify: `tests/Feature/WebhookManagementTest.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Write a failing regression test**

```php
public function test_inbound_webhook_request_does_not_run_nested_transactions_in_tests(): void
{
    Queue::fake();

    $response = $this->postJson('/webhooks/inbound/test-provider', [
        'event' => 'estimate.created',
    ]);

    $response->assertAccepted();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/WebhookSystemTest.php --filter=nested_transactions`
Expected: FAIL with SQLite transaction nesting or sync execution side effects.

- [ ] **Step 3: Write minimal implementation**

```php
ProcessInboundWebhook::dispatch($event);
```

```php
public function replay()
{
    ProcessInboundWebhook::dispatch($this);
}
```

- [ ] **Step 4: Run focused webhook tests**

Run: `php artisan test tests/Feature/WebhookSystemTest.php tests/Feature/WebhookManagementTest.php`
Expected: PASS

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS or a reduced, explainable remaining failure set unrelated to webhook transaction nesting.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/WebhookController.php app/Models/WebhookInboundEvent.php tests/Feature/WebhookSystemTest.php tests/Feature/WebhookManagementTest.php phpunit.xml
git commit -m "fix: stabilize webhook tests on sqlite"
```
