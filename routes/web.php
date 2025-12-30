<?php

use App\Http\Controllers\EstimateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\WelcomeController::class)->name('welcome');

Route::get('/user-guide', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('guide.index');
Route::get('/user-guide/{page}', [\App\Http\Controllers\DocumentationController::class, 'show'])->name('guide.show');

// Portal (Signed Routes)
Route::group(['prefix' => 'portal', 'as' => 'portal.'], function () {
    Route::get('/estimates/{estimate}', [App\Http\Controllers\PortalController::class, 'show'])->name('show')->middleware('signed');
    Route::post('/estimates/{estimate}/accept', [App\Http\Controllers\PortalController::class, 'accept'])->name('accept')->middleware('signed');
    Route::post('/estimates/{estimate}/decline', [App\Http\Controllers\PortalController::class, 'decline'])->name('decline')->middleware('signed');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile (Standard Breeze)
    // AI Generation
    Route::post('/ai/generate-description', [App\Http\Controllers\GenerativeAIController::class, 'generate'])->name('ai.generate');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Estimates (Access controlled by Policy)
    Route::get('estimates/{estimate}/print', [EstimateController::class, 'print'])->name('estimates.print');
    Route::post('estimates/{estimate}/submit', [App\Http\Controllers\ApprovalController::class, 'submit'])->name('estimates.submit');
    Route::post('estimates/{estimate}/approve', [App\Http\Controllers\ApprovalController::class, 'approve'])->name('estimates.approve');
    Route::post('estimates/{estimate}/toggle-checklist', [App\Http\Controllers\ApprovalController::class, 'toggleChecklistItem'])->name('estimates.toggle-checklist');
    Route::post('estimates/{estimate}/reject', [App\Http\Controllers\ApprovalController::class, 'reject'])->name('estimates.reject');
    Route::post('estimates/{estimate}/version', [EstimateController::class, 'createVersion'])->name('estimates.version');
    Route::post('estimates/{estimate}/revert', [EstimateController::class, 'revertToDraft'])->name('estimates.revert');
    Route::get('estimates/{estimate}/pdf', [EstimateController::class, 'downloadPdf'])->name('estimates.pdf');
    Route::post('estimates/preview', [EstimateController::class, 'preview'])->name('estimates.preview');
    Route::post('estimates/batch-download', [EstimateController::class, 'batchDownload'])->name('estimates.batch-download');

    // Analytics
    Route::get('/estimates/{estimate}/analytics', [App\Http\Controllers\AnalyticsController::class, 'dashboard'])->name('estimates.analytics');
    Route::get('/estimates/{estimate}/analytics/export', [App\Http\Controllers\AnalyticsController::class, 'export'])->name('estimates.analytics.export');

    Route::resource('estimates', EstimateController::class);

    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');

    // Admin Only Routes
    Route::middleware(['role:super_admin,estimator_admin'])->group(function () {
        // Templates
        Route::resource('templates', App\Http\Controllers\RoomTemplateController::class);
        Route::resource('packages', App\Http\Controllers\ItemPackageController::class);

        // Settings
        Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('/settings', [App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');

        // Products & Categories
        Route::resource('categories', \App\Http\Controllers\ProductCategoryController::class);
        Route::resource('products', ProductController::class);

        // Approvals
        Route::get('/approvals', [App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');

        // User Management (Super Admin Only)
        Route::resource('users', App\Http\Controllers\UserController::class);
        Route::get('/permissions', [App\Http\Controllers\PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{role}/edit', [App\Http\Controllers\PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{role}', [App\Http\Controllers\PermissionController::class, 'update'])->name('permissions.update');


        // Clients
        Route::resource('clients', App\Http\Controllers\ClientController::class);

        // Coupon Codes
        Route::resource('coupons', App\Http\Controllers\CouponCodeController::class);
        Route::post('coupons/validate-code', [App\Http\Controllers\CouponCodeController::class, 'verify'])->name('coupons.validate');

        // Comments
        Route::post('estimates/{estimate}/comments', [App\Http\Controllers\CommentController::class, 'store'])->name('comments.store');
        Route::get('estimates/{estimate}/comments', [App\Http\Controllers\CommentController::class, 'index'])->name('comments.index');
        Route::patch('comments/{comment}/read', [App\Http\Controllers\CommentController::class, 'markAsRead'])->name('comments.read');
        Route::post('estimates/{estimate}/comments/read-all', [App\Http\Controllers\CommentController::class, 'markAllAsRead'])->name('comments.readAll');
        Route::delete('comments/{comment}', [App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');

        // Product Library
        Route::resource('products', App\Http\Controllers\ProductController::class);
        Route::get('products/actions/template', [App\Http\Controllers\ProductController::class, 'downloadTemplate'])->name('products.template');
        Route::post('products/actions/import', [App\Http\Controllers\ProductController::class, 'import'])->name('products.import');
        Route::get('products/pending/list', [App\Http\Controllers\ProductController::class, 'pending'])->name('products.pending');
        Route::post('products/{product}/suggest', [App\Http\Controllers\ProductController::class, 'suggest'])->name('products.suggest');
        Route::post('products/{product}/approve', [App\Http\Controllers\ProductController::class, 'approve'])->name('products.approve');
        Route::post('products/{product}/retire', [App\Http\Controllers\ProductController::class, 'retire'])->name('products.retire');
        Route::post('products/{product}/activate', [App\Http\Controllers\ProductController::class, 'activate'])->name('products.activate');

        // Estimate Items
        Route::post('estimate-items/{item}/duplicate', [App\Http\Controllers\EstimateController::class, 'duplicateItem'])->name('estimate-items.duplicate');

        // Estimate Admin Actions
        Route::post('estimates/{estimate}/copy', [App\Http\Controllers\EstimateController::class, 'copy'])->name('estimates.copy');
        Route::post('estimates/{estimate}/mark-as/{status}', [App\Http\Controllers\EstimateController::class, 'markAs'])->name('estimates.mark-as');
        Route::post('estimates/{estimate}/send', [App\Http\Controllers\EstimateController::class, 'sendToClient'])->name('estimates.send');

        // Tracking
        Route::get('t/{estimate}/pixel.png', [App\Http\Controllers\TrackingController::class, 'pixel'])->name('tracking.pixel');

        // Tasks
        Route::resource('tasks', App\Http\Controllers\TaskController::class);
        Route::post('tasks/{task}/complete', [App\Http\Controllers\TaskController::class, 'complete'])->name('tasks.complete');

        // Activity Logs
        Route::get('activities', [App\Http\Controllers\ActivityLogController::class, 'index'])->name('activities.index');
        Route::get('activities/{activity}', [App\Http\Controllers\ActivityLogController::class, 'show'])->name('activities.show');

        // Reminders
        Route::get('reminders', [App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
        Route::post('reminders', [App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
        Route::delete('reminders/{reminder}', [App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');
        Route::post('reminders/{reminder}/read', [App\Http\Controllers\ReminderController::class, 'markAsRead'])->name('reminders.read');

        // Search
        Route::get('search', App\Http\Controllers\SearchController::class)->name('search');

        // Notifications
        Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

        // Perfex
        Route::get('/perfex/leads', [App\Http\Controllers\PerfexController::class, 'index'])->name('perfex.index');
        Route::get('/perfex/search', [App\Http\Controllers\PerfexController::class, 'searchClients'])->name('perfex.search');
        Route::post('/perfex/import', [App\Http\Controllers\PerfexController::class, 'import'])->name('perfex.import');
        Route::post('/estimates/{estimate}/sync', [App\Http\Controllers\PerfexController::class, 'sync'])->name('estimates.sync');

        // PDF Custom Templates
        Route::post('/pdf-templates/preview', [App\Http\Controllers\PdfTemplateController::class, 'preview'])->name('pdf-templates.preview');
        Route::post('/pdf-templates/{pdfTemplate}/restore/{version}', [App\Http\Controllers\PdfTemplateController::class, 'restore'])->name('pdf-templates.restore');
        Route::resource('pdf-templates', App\Http\Controllers\PdfTemplateController::class);
    });

    // Approval Chains (Super Admin Only)
    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('approval-chains', App\Http\Controllers\ApprovalChainController::class);
        Route::post('approval-chains/{approvalChain}/set-default', [App\Http\Controllers\ApprovalChainController::class, 'setDefault'])->name('approval-chains.set-default');
    });
});

// Webhook for Perfex (No CSRF check)
Route::post('/webhooks/perfex', [App\Http\Controllers\PerfexWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__ . '/auth.php';
