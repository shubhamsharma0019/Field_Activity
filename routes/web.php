<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::view('/admin/login', 'admin_login.index')->name('admin.login');

// Laravel uses this route name when an unauthenticated browser opens a protected URL.
Route::view('/login', 'admin_login.index')->name('login');

Route::view('/dashboard', 'dashboard.index')->name('web.dashboard');

Route::view('/companies', 'companies.index')->name('web.companies.index');

Route::view('/companies/create', 'companies.create')->name('web.companies.create');

Route::view('/users', 'users.index')->name('web.users.index');

Route::view('/users/create', 'users.create')->name('web.users.create');

Route::view('/projects', 'projects.index')->name('web.projects.index');

Route::view('/projects/create', 'projects.create')->name('web.projects.create');

Route::view('/activity-types', 'activity_types.index')->name('web.activity-types.index');

Route::view('/activity-types/create', 'activity_types.create')->name('web.activity-types.create');

Route::view('/assignments', 'assignments.index')->name('web.assignments.index');

Route::view('/assignments/create', 'assignments.create')->name('web.assignments.create');

Route::view('/submissions', 'submissions.index')->name('web.submissions.index');

Route::view('/activity-sessions', 'activity_sessions.index')->name('web.activity-sessions.index');

Route::view('/activity-sessions/live-tracking', 'activity_sessions.live')->name('web.activity-sessions.live');

Route::view('/activity-sessions/SES0001', 'activity_sessions.show')->name('web.activity-sessions.show');

Route::view('/activity-sessions/map', 'activity_sessions.map')->name('web.activity-sessions.map');

Route::view('/reports', 'reports.index')->name('web.reports.index');

Route::view('/settings', 'settings.index')->name('web.settings.index');

Route::view('/activity-updates', 'activity_updates.index')->name('web.activity-updates.index');
