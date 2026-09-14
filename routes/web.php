<?php

use App\Http\Controllers\AiRequests\AiRequestPageController;
use App\Http\Controllers\AiRequests\SubmitAiRequestController;
use App\Http\Controllers\AiResponses\AiResponseController;
use App\Http\Controllers\ActivityLogs\ActivityLogController;
use App\Http\Controllers\Audits\AuditController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Roles\ToggleRolePermissionController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('ai-requests', AiRequestPageController::class)->name('ai-requests.index');
    Route::post('ai-requests', SubmitAiRequestController::class)->name('ai-requests.store');
    Route::resource('ai-responses', AiResponseController::class)->only(['index', 'show']);

    Route::resource('users', UserController::class)->except(['create', 'edit']);
    Route::resource('roles', RoleController::class);

    Route::put('roles/{role}/permissions/{permission}', ToggleRolePermissionController::class)
        ->name('roles.permissions.toggle');

    Route::get('audits', [AuditController::class, 'index'])->name('audits.index');
    Route::get('audits/{audit}', [AuditController::class, 'show'])->name('audits.show');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
});

require __DIR__.'/settings.php';
