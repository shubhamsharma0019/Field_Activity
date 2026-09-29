<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ActivitySessionPageController;
use App\Http\Controllers\ActivityTypePageController;
use App\Http\Controllers\ActivityUpdatePageController;
use App\Http\Controllers\AssignmentPageController;
use App\Http\Controllers\CompanyPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectPageController;
use App\Http\Controllers\ProjectActivityPageController;
use App\Http\Controllers\ReportPageController;
use App\Http\Controllers\SettingsPageController;
use App\Http\Controllers\SubmissionPageController;
use App\Http\Controllers\UserPageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

// Laravel uses this route name when an unauthenticated browser opens a protected URL.
Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');

Route::post('/admin/logout', [AdminAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('web.dashboard');

    Route::get('/companies', [CompanyPageController::class, 'index'])->name('web.companies.index');

    Route::get('/companies/create', [CompanyPageController::class, 'create'])->name('web.companies.create');

    Route::post('/companies', [CompanyPageController::class, 'store'])->name('web.companies.store');

    Route::get('/users', [UserPageController::class, 'index'])->name('web.users.index');

    Route::view('/users/create', 'users.create')->name('web.users.create');

    Route::get('/projects', [ProjectPageController::class, 'index'])->name('web.projects.index');

    Route::get('/projects/create', [ProjectPageController::class, 'create'])->name('web.projects.create');

    Route::post('/projects', [ProjectPageController::class, 'store'])->name('web.projects.store');

    Route::get('/activity-types', [ActivityTypePageController::class, 'index'])->name('web.activity-types.index');

    Route::post('/activity-types', [ActivityTypePageController::class, 'store'])->name('web.activity-types.store');

    Route::view('/activity-types/create', 'activity_types.create')->name('web.activity-types.create');

    Route::get('/assignments', [AssignmentPageController::class, 'index'])->name('web.assignments.index');

    Route::post('/assignments', [AssignmentPageController::class, 'store'])->name('web.assignments.store');

    Route::view('/assignments/create', 'assignments.create')->name('web.assignments.create');

    Route::get('/project-activities/create', [ProjectActivityPageController::class, 'create'])->name('web.project-activities.create');

    Route::post('/project-activities', [ProjectActivityPageController::class, 'store'])->name('web.project-activities.store');

    Route::get('/submissions', [SubmissionPageController::class, 'index'])->name('web.submissions.index');

    Route::post('/submissions/{submission}/review', [SubmissionPageController::class, 'review'])->name('web.submissions.review');

    Route::get('/activity-sessions', [ActivitySessionPageController::class, 'index'])->name('web.activity-sessions.index');

    Route::post('/activity-sessions/{session}/review', [ActivitySessionPageController::class, 'review'])->name('web.activity-sessions.review');

    Route::view('/activity-sessions/live-tracking', 'activity_sessions.live')->name('web.activity-sessions.live');

    Route::view('/activity-sessions/SES0001', 'activity_sessions.show')->name('web.activity-sessions.show');

    Route::view('/activity-sessions/map', 'activity_sessions.map')->name('web.activity-sessions.map');

    Route::get('/reports', [ReportPageController::class, 'index'])->name('web.reports.index');

    Route::get('/settings', [SettingsPageController::class, 'index'])->name('web.settings.index');

    Route::post('/settings', [SettingsPageController::class, 'update'])->name('web.settings.update');

    Route::get('/settings/export', [SettingsPageController::class, 'export'])->name('web.settings.export');

    Route::post('/settings/clear-cache', [SettingsPageController::class, 'clearCache'])->name('web.settings.clear-cache');

    Route::get('/activity-updates', [ActivityUpdatePageController::class, 'index'])->name('web.activity-updates.index');
});
