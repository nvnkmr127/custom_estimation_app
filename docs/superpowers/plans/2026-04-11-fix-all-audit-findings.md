# Fix All Audit Findings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Eliminate broken pages/links, align navigation + authorization with route enforcement, complete CRUD affordances, and normalize UI↔backend contracts across the application.

**Architecture:** Keep existing Laravel + Livewire patterns. Stabilize runtime breakers first, then normalize auth/response contracts, then complete CRUD and remove dead links/orphans with test coverage.

**Tech Stack:** Laravel, Blade, Livewire, PHPUnit, Tailwind, Alpine.js

---

## Files to Touch (Map)

**Create**
- `app/Livewire/NotificationList.php`
- `resources/views/livewire/notification-list.blade.php`
- `tests/Feature/NotificationsPageTest.php`
- `tests/Feature/SettingsAuthorizationTest.php`
- `tests/Feature/NavigationGatingTest.php`
- `tests/Feature/AutomationTemplatesNavigationTest.php`
- `resources/views/admin/webhooks/show.blade.php` (if implementing show UI)

**Modify**
- `routes/web.php`
- `app/Services/Navigation/NavigationService.php`
- `resources/views/components/app-layout.blade.php`
- `app/Http/Controllers/SettingsController.php`
- `resources/views/clients/_table.blade.php`
- `resources/views/tasks/index.blade.php`
- `resources/views/reminders/index.blade.php`
- `app/Http/Controllers/Admin/AutomationTemplateController.php` (if adding HTML page route)
- `app/Http/Controllers/Admin/WebhookEndpointController.php` (add `show()`)
- `resources/views/components/global-search.blade.php`
- `resources/views/welcome.blade.php`
- `resources/views/livewire/dashboard.blade.php`
- `resources/views/livewire/estimates/show-estimate.blade.php`
- `resources/views/estimates/show.blade.php` (if still in use)
- `app/Livewire/Admin/Webhooks/Index.php` (authorize `toggleStatus`)
- `app/Http/Controllers/ProductController.php` (normalize retire/activate for HTML)

---

## Task 1: Fix Notifications Page Runtime Breaker

**Files:**
- Create: `app/Livewire/NotificationList.php`
- Create: `resources/views/livewire/notification-list.blade.php`
- Test: `tests/Feature/NotificationsPageTest.php`
- Verify: `resources/views/notifications/index.blade.php` already mounts `<livewire:notification-list />`

- [ ] **Step 1: Write failing feature test**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_index_renders(): void
    {
        $user = User::factory()->create(['role' => 'estimator']);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications');
    }
}
```

- [ ] **Step 2: Run the test (expect failure due to missing Livewire component)**

Run: `php artisan test --filter=NotificationsPageTest`  
Expected: FAIL mentioning missing `notification-list` component/class.

- [ ] **Step 3: Create Livewire component**

Create: `app/Livewire/NotificationList.php`

```php
<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    public function markAsRead(string $notificationId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return view('livewire.notification-list', [
            'notifications' => $user->notifications()->latest()->paginate(25),
        ]);
    }
}
```

- [ ] **Step 4: Create Livewire view**

Create: `resources/views/livewire/notification-list.blade.php`

```blade
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-slate-900">Notifications</h1>
        <button
            type="button"
            wire:click="markAllAsRead"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
        >
            Mark all as read
        </button>
    </div>

    <div class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
        @forelse ($notifications as $notification)
            <div class="flex items-start justify-between gap-4 p-4">
                <div class="min-w-0">
                    <div class="text-sm text-slate-900">
                        {{ data_get($notification->data, 'message', 'Notification received') }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @php($link = data_get($notification->data, 'link'))
                    @if ($link)
                        <a class="text-sm font-medium text-slate-700 hover:text-slate-900" href="{{ $link }}">
                            View
                        </a>
                    @endif

                    @if (is_null($notification->read_at))
                        <button
                            type="button"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Mark read
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-6 text-sm text-slate-600">No notifications.</div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=NotificationsPageTest`  
Expected: PASS

- [ ] **Step 6: Commit (optional)**

```bash
git add app/Livewire/NotificationList.php resources/views/livewire/notification-list.blade.php tests/Feature/NotificationsPageTest.php
git commit -m "fix: restore notifications index page"
```

---

## Task 2: Fix Automation Templates Menu Target (No JSON in Navigation)

**Files:**
- Modify: `app/Services/Navigation/NavigationService.php`
- Modify: `resources/views/admin/automation/index.blade.php` (or the file that initializes templates modal)
- Test: `tests/Feature/AutomationTemplatesNavigationTest.php`

**Decision:** Keep `automation.templates.index` as JSON for XHR. Change navigation to point to `automation.index?open_templates=1` and auto-open the modal.

- [ ] **Step 1: Write failing test (nav target returns HTML)**

Create: `tests/Feature/AutomationTemplatesNavigationTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTemplatesNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_templates_navigation_lands_on_html_page(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get(route('automation.index', ['open_templates' => 1]));
        $response->assertOk();
        $response->assertHeader('content-type', fn ($v) => str_contains($v, 'text/html'));
    }
}
```

- [ ] **Step 2: Update nav item to point to automation index with query**

Modify: `app/Services/Navigation/NavigationService.php`  
Change the “Automation Templates” item route from `automation.templates.index` to `automation.index` with params.

```php
[
    'name' => 'Automation Templates',
    'route' => route('automation.index', ['open_templates' => 1]),
    'icon' => 'DocumentDuplicateIcon',
],
```

- [ ] **Step 3: Auto-open templates modal when `open_templates=1`**

Modify the automation index page’s JS that controls the templates modal (currently fetches from `route('automation.templates.index')`).

Implementation shape (exact placement depends on current script structure in `resources/views/admin/automation/partials/scripts.blade.php`):

```js
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('open_templates') === '1') {
    openTemplatesModal();
  }
});
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=AutomationTemplatesNavigationTest`  
Expected: PASS

- [ ] **Step 5: Commit (optional)**

```bash
git add app/Services/Navigation/NavigationService.php resources/views/admin/automation/**/*.blade.php tests/Feature/AutomationTemplatesNavigationTest.php
git commit -m "fix: point automation templates nav to HTML page"
```

---

## Task 3: Enforce Super-Admin-Only Nav Items (Prevent 403 Dead Ends)

**Files:**
- Modify: `app/Services/Navigation/NavigationService.php`
- Modify: `resources/views/components/app-layout.blade.php`
- Test: `tests/Feature/NavigationGatingTest.php`

**Scope:** Approval chains/checklists/revision checklists, webhooks, backup must not render for `estimator_admin` (super_admin-only).

- [ ] **Step 1: Write failing test for nav gating**

Create: `tests/Feature/NavigationGatingTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_admin_does_not_see_super_admin_only_links(): void
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Backup & Restore')
            ->assertDontSee('Webhook Endpoints')
            ->assertDontSee('Approval Flows')
            ->assertDontSee('Approval Checklists')
            ->assertDontSee('Revision Checklists');
    }
}
```

- [ ] **Step 2: Fix NavigationService role logic**

Modify `app/Services/Navigation/NavigationService.php` to:
- Only add approval-flows/checklists/revision-checklists when `$isSuperAdmin`.
- Only add webhooks + backup when `$isSuperAdmin`.

- [ ] **Step 3: Make layout renderer enforce item-level `role` metadata**

Modify `resources/views/components/app-layout.blade.php` where it iterates `$navigation`. Add an early `continue` when an item has a `role` and user lacks it.

Blade shape:

```blade
@php($requiredRole = $item['role'] ?? null)
@if ($requiredRole && ! auth()->user()->hasRole($requiredRole))
    @continue
@endif
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=NavigationGatingTest`  
Expected: PASS

- [ ] **Step 5: Commit (optional)**

```bash
git add app/Services/Navigation/NavigationService.php resources/views/components/app-layout.blade.php tests/Feature/NavigationGatingTest.php
git commit -m "fix: align sidebar links with super-admin-only routes"
```

---

## Task 4: Settings Authorization Parity (Edit vs Update)

**Files:**
- Modify: `app/Http/Controllers/SettingsController.php`
- Test: `tests/Feature/SettingsAuthorizationTest.php`

- [ ] **Step 1: Write failing test (estimator_admin cannot update settings)**

Create: `tests/Feature/SettingsAuthorizationTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_admin_cannot_update_settings_without_manage_settings_permission(): void
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);

        $this->actingAs($user)
            ->post(route('settings.update'), [])
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Add authorization checks to all settings mutations**

Modify: `app/Http/Controllers/SettingsController.php`

Add at the start of:
- `update()`
- `testEmail()`
- `deleteGalleryImage()`

```php
$this->authorize('manage_settings');
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --filter=SettingsAuthorizationTest`  
Expected: PASS

- [ ] **Step 4: Commit (optional)**

```bash
git add app/Http/Controllers/SettingsController.php tests/Feature/SettingsAuthorizationTest.php
git commit -m "fix: enforce manage_settings gate for settings mutations"
```

---

## Task 5: Normalize Webhooks Resource (Add Missing show() + Authorization Tightening)

**Files:**
- Modify: `app/Http/Controllers/Admin/WebhookEndpointController.php`
- Create: `resources/views/admin/webhooks/show.blade.php`
- Modify: `app/Livewire/Admin/Webhooks/Index.php`

- [ ] **Step 1: Add `show()` to controller**

Modify: `app/Http/Controllers/Admin/WebhookEndpointController.php`

```php
public function show(\App\Models\WebhookEndpoint $webhook)
{
    return view('admin.webhooks.show', compact('webhook'));
}
```

- [ ] **Step 2: Add show view**

Create: `resources/views/admin/webhooks/show.blade.php`

```blade
<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold text-slate-900">{{ $webhook->name }}</h1>
            <a class="text-sm font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('admin.webhooks.edit', $webhook) }}">
                Edit
            </a>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-slate-500">URL</dt>
                    <dd class="mt-1 break-all">{{ $webhook->url }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Status</dt>
                    <dd class="mt-1">{{ $webhook->is_active ? 'Active' : 'Inactive' }}</dd>
                </div>
            </dl>
        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 3: Authorize toggleStatus in Livewire**

Modify: `app/Livewire/Admin/Webhooks/Index.php`  
In `toggleStatus($id)`, add:

```php
$webhook = WebhookEndpoint::findOrFail($id);
$this->authorize('update', $webhook);
$webhook->update(['is_active' => ! $webhook->is_active]);
```

- [ ] **Step 4: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 5: Commit (optional)**

```bash
git add app/Http/Controllers/Admin/WebhookEndpointController.php resources/views/admin/webhooks/show.blade.php app/Livewire/Admin/Webhooks/Index.php
git commit -m "fix: complete admin webhooks resource and tighten auth"
```

---

## Task 6: Complete CRUD Affordances (Clients + Tasks Delete)

**Files:**
- Modify: `resources/views/clients/_table.blade.php`
- Modify: `resources/views/tasks/index.blade.php`

- [ ] **Step 1: Add delete form/button to clients table**

Modify: `resources/views/clients/_table.blade.php`

Blade snippet to add in actions column:

```blade
<form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Delete this client?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-500">
        Delete
    </button>
</form>
```

- [ ] **Step 2: Add delete button to tasks index**

Modify: `resources/views/tasks/index.blade.php`

```blade
<form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-500">
        Delete
    </button>
</form>
```

- [ ] **Step 3: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 4: Commit (optional)**

```bash
git add resources/views/clients/_table.blade.php resources/views/tasks/index.blade.php
git commit -m "feat: add delete actions for clients and tasks"
```

---

## Task 7: Add Reminders Create UI (Match Existing POST Route)

**Files:**
- Modify: `resources/views/reminders/index.blade.php`

- [ ] **Step 1: Add create form to reminders index**

Implementation assumes `ReminderController@store` accepts a `message` string (confirm by inspecting controller + validation).

Blade snippet near top:

```blade
<form method="POST" action="{{ route('reminders.store') }}" class="rounded-lg border border-slate-200 bg-white p-4">
    @csrf
    <label class="block text-sm font-medium text-slate-700">New reminder</label>
    <textarea name="message" required class="mt-2 w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
    <div class="mt-3 flex justify-end">
        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Add reminder
        </button>
    </div>
</form>
```

- [ ] **Step 2: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 3: Commit (optional)**

```bash
git add resources/views/reminders/index.blade.php
git commit -m "feat: add reminders create form"
```

---

## Task 8: Fix Dead Links (`href="#"`) Across Core Surfaces

**Files:**
- Modify: `resources/views/welcome.blade.php`
- Modify: `resources/views/livewire/dashboard.blade.php`
- Modify: `resources/views/livewire/estimates/show-estimate.blade.php`
- Modify: `resources/views/estimates/show.blade.php` (if reachable)

- [ ] **Step 1: Welcome logo + Learn more**

Replace `href="#"`:
- Logo → `url('/')`
- Learn more → `route('guide.index')`

- [ ] **Step 2: Dashboard “View All Opportunities”**

Replace with: `route('estimates.index')` (and add filter query if one exists)

- [ ] **Step 3: Estimate “View Chain”**

If super_admin: link to `approval-chains.show` when chain exists; otherwise remove link or replace with a non-clickable label to avoid dead control for non-super-admins.

- [ ] **Step 4: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 5: Commit (optional)**

```bash
git add resources/views/welcome.blade.php resources/views/livewire/dashboard.blade.php resources/views/livewire/estimates/show-estimate.blade.php resources/views/estimates/show.blade.php
git commit -m "fix: remove dead links from core UI"
```

---

## Task 9: Global Search Robustness (Encode + Error State + Correct Shortcut Copy)

**Files:**
- Modify: `resources/views/components/global-search.blade.php`

- [ ] **Step 1: Encode query**

Change:

```js
fetch('/search?q=' + search)
```

To:

```js
fetch('/search?q=' + encodeURIComponent(search))
```

- [ ] **Step 2: Add catch + error UI state**

Implement component state flags (e.g., `errorMessage`) and render an error line in the modal results area.

- [ ] **Step 3: Fix shortcut copy**

Update UI text from “Type / to search” to “Press ⌘K / Ctrl+K to search” to match bindings.

- [ ] **Step 4: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 5: Commit (optional)**

```bash
git add resources/views/components/global-search.blade.php
git commit -m "fix: harden global search modal fetch behavior"
```

---

## Task 10: Normalize Product Retire/Activate for Web UI (Avoid Raw JSON Pages)

**Files:**
- Modify: `app/Http/Controllers/ProductController.php`

- [ ] **Step 1: Return redirects for non-JSON requests**

Update `retire()` and `activate()` to:
- If `$request->wantsJson()` return JSON (existing behavior).
- Else `return back()->with('success', ...)`.

Example:

```php
if ($request->wantsJson()) {
    return response()->json(['message' => 'Product retired successfully']);
}

return back()->with('success', 'Product retired successfully.');
```

- [ ] **Step 2: Run tests**

Run: `php artisan test`  
Expected: PASS

- [ ] **Step 3: Commit (optional)**

```bash
git add app/Http/Controllers/ProductController.php
git commit -m "fix: redirect on product retire/activate for web requests"
```

---

## Final Verification

- [ ] Run full suite: `php artisan test`
- [ ] Smoke-check key pages manually (dev server):
  - `/notifications` renders and can mark read
  - Sidebar no longer shows super-admin-only links for estimator_admin
  - Automation Templates nav opens automation page (not JSON)
  - Settings update blocked for estimator_admin without manage_settings permission

