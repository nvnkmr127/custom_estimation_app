<?php

use App\Http\Controllers\EstimateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\WelcomeController::class)->name('welcome');

Route::get('/user-guide', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('guide.index');
Route::get('/user-guide/{page}', [\App\Http\Controllers\DocumentationController::class, 'show'])->name('guide.show');

// SSO Authentication
Route::get('/sso/login', [App\Http\Controllers\Auth\SsoController::class, 'login'])
    ->name('sso.login')
    ->middleware('guest');

Route::get('/sso/callback', [App\Http\Controllers\Auth\SsoController::class, 'callback'])
    ->name('sso.callback')
    ->middleware(['guest', 'throttle:6,1']);

// SSO Config Sync (Used by Auth Portal)
Route::get('/api/sso/sync', [App\Http\Controllers\Auth\SsoSyncController::class, 'index']);

// Portal (Signed Routes)
Route::group(['prefix' => 'portal', 'as' => 'portal.'], function () {
    Route::get('/estimates/{estimate}', [App\Http\Controllers\PortalController::class, 'show'])->name('show')->middleware('signed');
    Route::get('/estimates/{estimate}/download', [App\Http\Controllers\PortalController::class, 'download'])->name('download')->middleware('signed');
    Route::post('/estimates/{estimate}/accept', [App\Http\Controllers\PortalController::class, 'accept'])->name('accept')->middleware('signed');
    Route::post('/estimates/{estimate}/decline', [App\Http\Controllers\PortalController::class, 'decline'])->name('decline')->middleware('signed');
    Route::post('/estimates/{estimate}/comments', [App\Http\Controllers\PortalController::class, 'comment'])->name('comment')->middleware('signed');
    Route::post('/estimates/{estimate}/request-changes', [App\Http\Controllers\PortalController::class, 'requestChanges'])->name('request-changes')->middleware('signed');
    Route::post('/estimates/{estimate}/request-call', [App\Http\Controllers\PortalController::class, 'requestCall'])->name('request-call')->middleware('signed');
    
    // Internal Team Preview (Restricted to logged-in staff)
    Route::get('/preview/estimates', [App\Http\Controllers\PortalController::class, 'previewList'])
        ->name('preview-list')
        ->middleware(['auth', 'role:super_admin,estimator_admin,estimator_manager,estimator']);
});

// Tracking (Public)
Route::get('t/{estimate}/pixel.png', [App\Http\Controllers\TrackingController::class, 'pixel'])->name('tracking.pixel');
// Email Tracking
Route::get('tracking/open/{id}', [App\Http\Controllers\EmailTrackingController::class, 'trackOpen'])->name('tracking.open');
Route::get('tracking/click/{id}', [App\Http\Controllers\EmailTrackingController::class, 'trackClick'])->name('tracking.click');

// Short URL Redirects
Route::get('s/{code}', [App\Http\Controllers\ShortUrlController::class, 'redirect'])->name('short-url.redirect');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', App\Livewire\Dashboard::class)->name('dashboard');

    // Profile (Standard Breeze)
    // AI Generation
    Route::post('/ai/generate-description', [App\Http\Controllers\GenerativeAIController::class, 'generate'])->name('ai.generate');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/preferences', [\App\Http\Controllers\User\NotificationPreferenceController::class, 'update'])->name('preferences.update');

    // Estimates (Access controlled by Policy)
    Route::get('estimates/{estimate}/print', [EstimateController::class, 'print'])->name('estimates.print');
    Route::post('estimates/{estimate}/submit', [App\Http\Controllers\ApprovalController::class, 'submit'])->name('estimates.submit');
    Route::post('estimates/{estimate}/approve', [App\Http\Controllers\ApprovalController::class, 'approve'])->name('estimates.approve');
    Route::post('estimates/{estimate}/toggle-checklist', [App\Http\Controllers\ApprovalController::class, 'toggleChecklistItem'])->name('estimates.toggle-checklist');
    Route::post('estimates/{estimate}/reject', [App\Http\Controllers\ApprovalController::class, 'reject'])->name('estimates.reject');
    Route::post('estimates/{estimate}/request-changes', [App\Http\Controllers\ApprovalController::class, 'requestChanges'])->name('estimates.request-changes');
    Route::post('estimates/{estimate}/version', [EstimateController::class, 'createVersion'])->name('estimates.version');
    Route::post('estimates/{estimate}/revert', [EstimateController::class, 'revertToDraft'])->name('estimates.revert');
    Route::get('estimates/{estimate}/pdf', [EstimateController::class, 'downloadPdf'])->name('estimates.pdf');
    Route::post('estimates/preview', [EstimateController::class, 'preview'])->name('estimates.preview');
    Route::post('estimates/calculate', [EstimateController::class, 'calculate'])->name('estimates.calculate');
    Route::post('estimates/batch-download', [EstimateController::class, 'batchDownload'])->name('estimates.batch-download');
    Route::get('estimates/{estimate}/portal-preview', [EstimateController::class, 'portalPreview'])->name('estimates.portal-preview');

    // Analytics
    Route::get('/estimates/{estimate}/analytics', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('estimates.analytics');
    Route::get('/estimates/{estimate}/analytics/export', [App\Http\Controllers\AnalyticsController::class, 'export'])->name('estimates.analytics.export');

    // Estimates Show (Livewire Component)
    Route::resource('estimates', EstimateController::class)->except(['show'])->middleware('estimate.lock');

    // Estimates Show (Livewire Component)
    Route::get('/estimates/{estimate}', \App\Livewire\Estimates\ShowEstimate::class)->name('estimates.show');

    Route::get('/reports', \App\Livewire\Reports\Index::class)->name('reports.index');

    // General Resources (Protected by Policies)
    Route::get('/approvals', [App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');
    Route::resource('clients', App\Http\Controllers\ClientController::class);

    // Comments & Notes
    Route::post('estimates/{estimate}/comments', [App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
    Route::get('estimates/{estimate}/comments', [App\Http\Controllers\CommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/read', [App\Http\Controllers\CommentController::class, 'markAsRead'])->name('comments.read');
    Route::post('estimates/{estimate}/comments/read-all', [App\Http\Controllers\CommentController::class, 'markAllAsRead'])->name('comments.readAll');
    Route::patch('comments/{comment}/status', [App\Http\Controllers\CommentController::class, 'updateStatus'])->name('comments.status');
    Route::delete('comments/{comment}', [App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

    // Product Library
    Route::resource('products', App\Http\Controllers\ProductController::class)->except(['show']);
    Route::get('products/actions/template', [App\Http\Controllers\ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/actions/import', [App\Http\Controllers\ProductController::class, 'import'])->name('products.import');
    Route::get('products/pending/list', [App\Http\Controllers\ProductController::class, 'pending'])->name('products.pending');
    Route::post('products/suggest', [App\Http\Controllers\ProductController::class, 'suggest'])->name('products.suggest');
    Route::post('products/{product}/approve', [App\Http\Controllers\ProductController::class, 'approve'])->name('products.approve');
    Route::post('products/{product}/retire', [App\Http\Controllers\ProductController::class, 'retire'])->name('products.retire');
    Route::post('products/{product}/activate', [App\Http\Controllers\ProductController::class, 'activate'])->name('products.activate');

    // Estimate Actions
    Route::post('estimate-items/{item}/duplicate', [App\Http\Controllers\EstimateController::class, 'duplicateItem'])->name('estimate-items.duplicate');
    Route::post('estimates/{estimate}/copy', [App\Http\Controllers\EstimateController::class, 'copy'])->name('estimates.copy');
    Route::post('estimates/{estimate}/mark-as/{status}', [App\Http\Controllers\EstimateController::class, 'markAs'])->name('estimates.mark-as');
    Route::post('estimates/{estimate}/reply', [App\Http\Controllers\EstimateController::class, 'storeComment'])->name('estimates.reply');
    Route::post('estimates/bulk-update', [App\Http\Controllers\EstimateController::class, 'bulkUpdate'])->name('estimates.bulk-update');
    Route::post('estimates/{estimate}/send', [App\Http\Controllers\EstimateController::class, 'sendToClient'])->name('estimates.send');
    Route::post('estimates/{estimate}/extend-expiry', [App\Http\Controllers\EstimateController::class, 'extendExpiry'])->name('estimates.extend-expiry');
    Route::post('estimates/{estimate}/followers', [App\Http\Controllers\EstimateController::class, 'addFollower'])->name('estimates.followers.add');
    Route::delete('estimates/{estimate}/followers/{user}', [App\Http\Controllers\EstimateController::class, 'removeFollower'])->name('estimates.followers.remove');

    // Version approval remains strict/admin mostly, but we can move it here and let Policy handle (Controller checks strictly anyway)
    Route::post('estimates/{estimate}/approve-version', [App\Http\Controllers\EstimateController::class, 'approveVersion'])->name('estimates.approve-version');
    Route::get('estimates/{estimate}/restore-history', [App\Http\Controllers\EstimateController::class, 'restoreHistory'])->name('estimates.restore-history');
    Route::post('estimates/{estimate}/restore-session', [App\Http\Controllers\EstimateController::class, 'restoreSession'])->name('estimates.restore-session');

    // Tasks
    Route::resource('tasks', App\Http\Controllers\TaskController::class);
    Route::post('tasks/{task}/complete', [App\Http\Controllers\TaskController::class, 'complete'])->name('tasks.complete');

    // Reminders
    Route::get('reminders', [App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders', [App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
    Route::delete('reminders/{reminder}', [App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::post('reminders/{reminder}/read', [App\Http\Controllers\ReminderController::class, 'markAsRead'])->name('reminders.read');

    // System Features
    Route::get('search', App\Http\Controllers\SearchController::class)->name('search');


    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Admin Only Routes
    Route::middleware(['role:super_admin,estimator_admin'])->group(function () {
        // Templates
        Route::resource('templates', App\Http\Controllers\RoomTemplateController::class);
        Route::resource('email-templates', App\Http\Controllers\EmailTemplateController::class);
        Route::post('email-templates/preview', [App\Http\Controllers\EmailTemplateController::class, 'preview'])->name('email-templates.preview');
        Route::post('email-templates/{emailTemplate}/test', [App\Http\Controllers\EmailTemplateController::class, 'test'])->name('email-templates.test');

        Route::resource('brands', App\Http\Controllers\BrandController::class)->except(['show']);
        Route::resource('packages', App\Http\Controllers\ItemPackageController::class);

        // Plugins
        Route::get('/admin/plugins', \App\Livewire\Admin\Plugins\Index::class)->name('admin.plugins.index');

        // Settings
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [App\Http\Controllers\SettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::delete('/settings/gallery/{index}', [App\Http\Controllers\SettingsController::class, 'deleteGalleryImage'])->name('settings.gallery.destroy');

        // Nurture Settings
        Route::get('/settings/nurture', [App\Http\Controllers\Admin\NurtureSettingsController::class, 'index'])->name('settings.nurture');
        Route::post('/settings/nurture/update', [App\Http\Controllers\Admin\NurtureSettingsController::class, 'updateSettings'])->name('settings.nurture.update');
        Route::post('/settings/nurture/rules', [App\Http\Controllers\Admin\NurtureSettingsController::class, 'storeRule'])->name('settings.nurture.rules.store');
        Route::put('/settings/nurture/rules/{rule}', [App\Http\Controllers\Admin\NurtureSettingsController::class, 'updateRule'])->name('settings.nurture.rules.update');
        Route::delete('/settings/nurture/rules/{rule}', [App\Http\Controllers\Admin\NurtureSettingsController::class, 'destroyRule'])->name('settings.nurture.rules.destroy');

        // Products & Categories
        Route::resource('categories', \App\Http\Controllers\ProductCategoryController::class);



        // Coupon Codes
        Route::resource('coupons', App\Http\Controllers\CouponCodeController::class);
        Route::post('coupons/validate-code', [App\Http\Controllers\CouponCodeController::class, 'verify'])->name('coupons.validate');





        // Activity Logs
        Route::get('activities', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activities.index');
        Route::get('activities/{activity}', [App\Http\Controllers\ActivityLogController::class, 'show'])->name('activities.show');

        // Event Logs (System Events)
        Route::resource('event-logs', App\Http\Controllers\EventLogController::class)->only(['index', 'show']);




        // PDF Custom Templates
        Route::post('/pdf-templates/preview', [App\Http\Controllers\PdfTemplateController::class, 'preview'])->name('pdf-templates.preview');
        Route::post('/pdf-templates/{pdfTemplate}/restore/{version}', [App\Http\Controllers\PdfTemplateController::class, 'restore'])->name('pdf-templates.restore');
        Route::resource('pdf-templates', App\Http\Controllers\PdfTemplateController::class)->except(['show']);
        Route::resource('unit-types', App\Http\Controllers\UnitTypeController::class)->except(['create', 'edit', 'show']);

        // Automation Layer
        Route::post('automation/{automation}/version', [App\Http\Controllers\Admin\AutomationController::class, 'createVersion'])->name('automation.version');
        Route::post('automation/{automation}/duplicate', [App\Http\Controllers\Admin\AutomationController::class, 'duplicate'])->name('automation.duplicate');
        Route::post('automation/{automation}/steps/reorder', [App\Http\Controllers\Admin\AutomationController::class, 'reorderSteps'])->name('automation.steps.reorder');
        Route::patch('automation/steps/{step}/toggle', [App\Http\Controllers\Admin\AutomationController::class, 'toggleStep'])->name('automation.steps.toggle');
        Route::post('automation/steps/{step}/duplicate', [App\Http\Controllers\Admin\AutomationController::class, 'duplicateStep'])->name('automation.steps.duplicate');
        Route::get('automation/{automation}/logs', [App\Http\Controllers\Admin\AutomationController::class, 'getLogs'])->name('automation.logs');
        Route::get('automation/{automation}/metrics', [App\Http\Controllers\Admin\AutomationController::class, 'getMetrics'])->name('automation.metrics');
        Route::get('automation/{automation}/flowchart', [App\Http\Controllers\Admin\AutomationVisualizationController::class, 'flowchart'])->name('automation.flowchart');
        Route::get('automation/{automation}/timeline', [App\Http\Controllers\Admin\AutomationVisualizationController::class, 'timeline'])->name('automation.timeline');
        Route::post('automation/preview', [App\Http\Controllers\Admin\AutomationVisualizationController::class, 'preview'])->name('automation.preview');

        // Automation Analytics
        Route::get('automation/analytics/dashboard', [App\Http\Controllers\Admin\AutomationAnalyticsController::class, 'dashboard'])->name('automation.analytics.dashboard');
        Route::get('automation/{automation}/analytics', [App\Http\Controllers\Admin\AutomationAnalyticsController::class, 'show'])->name('automation.analytics.show');

        // Automation Scheduling
        Route::post('automation/{automation}/schedule', [App\Http\Controllers\Admin\AutomationScheduleController::class, 'store'])->name('automation.schedule.store');
        Route::get('automation/{automation}/schedules', [App\Http\Controllers\Admin\AutomationScheduleController::class, 'index'])->name('automation.schedule.index');
        Route::put('automation/schedule/{schedule}', [App\Http\Controllers\Admin\AutomationScheduleController::class, 'update'])->name('automation.schedule.update');
        Route::delete('automation/schedule/{schedule}', [App\Http\Controllers\Admin\AutomationScheduleController::class, 'destroy'])->name('automation.schedule.destroy');
        Route::patch('automation/schedule/{schedule}/toggle', [App\Http\Controllers\Admin\AutomationScheduleController::class, 'toggle'])->name('automation.schedule.toggle');

        // Automation Templates
        Route::get('automation/templates/list', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'index'])->name('automation.templates.index');
        Route::post('automation/templates/import', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'import'])->name('automation.templates.import');
        Route::get('automation/templates/{template}/export', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'export'])->name('automation.templates.export');
        Route::post('automation/templates/{template}/install', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'install'])->name('automation.templates.install');
        Route::post('automation/{automation}/save-as-template', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'saveAsTemplate'])->name('automation.templates.save');
        Route::delete('automation/templates/{template}', [App\Http\Controllers\Admin\AutomationTemplateController::class, 'destroy'])->name('automation.templates.destroy');

        // Automation Experiments
        Route::get('automation/experiments', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'index'])->name('automation.experiments.index');
        Route::get('automation/experiments/create', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'create'])->name('automation.experiments.create');
        Route::post('automation/experiments', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'store'])->name('automation.experiments.store');
        Route::get('automation/experiments/{experiment}', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'show'])->name('automation.experiments.show');
        Route::post('automation/experiments/{experiment}/start', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'start'])->name('automation.experiments.start');
        Route::post('automation/experiments/{experiment}/stop', [App\Http\Controllers\Admin\AutomationExperimentController::class, 'stop'])->name('automation.experiments.stop');

        Route::get('automation/create', function () {
            return redirect()->route('automation.index');
        })->name('automation.create.redirect');
        Route::resource('automation', App\Http\Controllers\Admin\AutomationController::class)->except(['show', 'create']);
    });

    // Approval Chains (Super Admin Only)
    Route::middleware(['role:super_admin'])->group(function () {
        // User Management (Super Admin Only)
        Route::get('users/trash', [App\Http\Controllers\UserController::class, 'trash'])->name('users.trash');
        Route::post('users/{id}/restore', [App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');
        Route::resource('users', App\Http\Controllers\UserController::class);
        Route::get('/permissions', [App\Http\Controllers\PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{role}/edit', [App\Http\Controllers\PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{role}', [App\Http\Controllers\PermissionController::class, 'update'])->name('permissions.update');

        Route::resource('approval-chains', App\Http\Controllers\ApprovalChainController::class);
        Route::post('approval-chains/{approvalChain}/set-default', [App\Http\Controllers\ApprovalChainController::class, 'setDefault'])->name('approval-chains.set-default');

        Route::resource('approval-checklists', App\Http\Controllers\ApprovalChecklistController::class);
        Route::resource('change-request-checklists', App\Http\Controllers\ChangeRequestChecklistController::class);

        // ── Backup Management ───────────────────────────────────────────────────
        Route::get('/backup', [App\Http\Controllers\BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup/run', [App\Http\Controllers\BackupController::class, 'runNow'])->name('backup.run');
        Route::post('/backup/settings', [App\Http\Controllers\BackupController::class, 'saveSettings'])->name('backup.settings');
        Route::get('/backup/{backup}/download', [App\Http\Controllers\BackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/{backup}/drive', [App\Http\Controllers\BackupController::class, 'uploadToDrive'])->name('backup.drive');
        Route::post('/backup/{backup}/restore', [App\Http\Controllers\BackupController::class, 'restore'])->name('backup.restore');
        Route::delete('/backup/{backup}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('backup.destroy');
        Route::get('/backup/test-drive', [App\Http\Controllers\BackupController::class, 'testDrive'])->name('backup.test-drive');
    });

    // Webhook Management (Super Admin)
    Route::middleware(['role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('webhooks', \App\Http\Controllers\Admin\WebhookEndpointController::class);

        // Webhook Catchers (Inbound)
        Route::get('webhook-catchers', \App\Livewire\Admin\Webhooks\InboundEndpoints::class)->name('webhooks.catchers.index');

        // Webhook Events Explorer
        Route::get('webhook-events', function () {
            return view('admin.webhooks.events.index');
        })->name('webhooks.events.index');

        Route::get('webhook-events/{event}', function (\App\Models\WebhookEvent $event) {
            return view('admin.webhooks.events.show', compact('event'));
        })->name('webhooks.events.show');

        // Dead Letter Queue
        Route::get('webhook-dlq', function () {
            return view('admin.webhooks.dlq.index');
        })->name('webhooks.dlq.index');

        // Structured Logs
        Route::get('webhook-logs', function () {
            return view('admin.webhooks.logs.index');
        })->name('webhooks.logs.index');
    });
});

// Unified Webhook Ingress
Route::any('/catch/{uuid}', [App\Http\Controllers\WebhookController::class, 'catch'])->name('webhooks.catch');
Route::any('/plugins/catch/{uuid}', [App\Http\Controllers\PluginController::class, 'catch'])->name('admin.plugins.catch');
Route::post('/webhooks/{provider}', [App\Http\Controllers\WebhookController::class, 'handle'])->name('webhooks.handle');


// Local Development Autologin
if (app()->environment('local')) {
    Route::get('/dev/login/{id?}', function ($id = null) {
        $user = $id ? \App\Models\User::find($id) : \App\Models\User::where('role', 'super_admin')->first() ?? \App\Models\User::first();

        if ($user) {
            auth()->login($user);
            return redirect()->route('dashboard')->with('success', "Logged in as {$user->name} ({$user->role})");
        }
        return "No user found to autologin. Please run migrations/seeders.";
    })->name('dev.login');
}

require __DIR__ . '/auth.php';



