<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ActivityTypeController;
use App\Http\Controllers\Api\ProjectActivityController;
use App\Http\Controllers\Api\ProjectAssignmentController;
use App\Http\Controllers\Api\ActivitySubmissionController;
use App\Http\Controllers\Api\ActivitySessionController;
use App\Http\Controllers\Api\ActivityUpdateController;
use App\Http\Controllers\Api\WorkerLocationController;
use App\Http\Controllers\Api\ActivityReportController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/me', [
        AuthController::class,
        'me'
    ]);

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::post('/logout-all', [
        AuthController::class,
        'logoutAll'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [
        UserController::class,
        'index'
    ]);

    Route::post('/users', [
        UserController::class,
        'store'
    ]);

    Route::get('/users/{id}', [
        UserController::class,
        'show'
    ]);

    Route::put('/users/{id}', [
        UserController::class,
        'update'
    ]);

    Route::patch('/users/{id}', [
        UserController::class,
        'update'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'companies',
        CompanyController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'projects',
        ProjectController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Types
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'activity-types',
        ActivityTypeController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Project Activities
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'project-activities',
        ProjectActivityController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Project Assignments
    |--------------------------------------------------------------------------
    */

    /*
     * Worker finishes a continuous tracking activity.
     *
     * in_progress
     *      ↓
     * pending_approval
     */
    Route::post(
        '/project-assignments/{id}/complete',
        [
            ProjectAssignmentController::class,
            'complete'
        ]
    );

    /*
     * Admin / Super Admin reviews
     * continuous tracking activity.
     *
     * pending_approval
     *      ↓
     * completed / rejected flow
     */
    Route::patch(
        '/project-assignments/{id}/review',
        [
            ProjectAssignmentController::class,
            'review'
        ]
    );

    Route::apiResource(
        'project-assignments',
        ProjectAssignmentController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Submissions
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-submissions',
        [
            ActivitySubmissionController::class,
            'index'
        ]
    );

    Route::post(
        '/activity-submissions',
        [
            ActivitySubmissionController::class,
            'store'
        ]
    );

    Route::get(
        '/activity-submissions/{id}',
        [
            ActivitySubmissionController::class,
            'show'
        ]
    );

    Route::patch(
        '/activity-submissions/{id}/review',
        [
            ActivitySubmissionController::class,
            'review'
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Sessions
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-sessions',
        [
            ActivitySessionController::class,
            'index'
        ]
    );

    Route::post(
        '/activity-sessions/start',
        [
            ActivitySessionController::class,
            'start'
        ]
    );

    Route::get(
        '/activity-sessions/{id}',
        [
            ActivitySessionController::class,
            'show'
        ]
    );

    Route::post(
        '/activity-sessions/{id}/complete',
        [
            ActivitySessionController::class,
            'complete'
        ]
    );

    Route::patch(
        '/activity-sessions/{id}/review',
        [
            ActivitySessionController::class,
            'review'
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Updates
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-updates',
        [
            ActivityUpdateController::class,
            'index'
        ]
    );

    Route::post(
        '/activity-updates',
        [
            ActivityUpdateController::class,
            'store'
        ]
    );

    Route::get(
        '/activity-updates/{id}',
        [
            ActivityUpdateController::class,
            'show'
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Worker Locations
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/worker-locations',
        [
            WorkerLocationController::class,
            'index'
        ]
    );

    Route::post(
        '/worker-locations',
        [
            WorkerLocationController::class,
            'store'
        ]
    );

    Route::get(
        '/worker-locations/{assignmentId}/latest',
        [
            WorkerLocationController::class,
            'latest'
        ]
    );

    Route::get(
        '/worker-locations/{assignmentId}/history',
        [
            WorkerLocationController::class,
            'history'
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-reports',
        [
            ActivityReportController::class,
            'index'
        ]
    );

    Route::post(
        '/activity-reports',
        [
            ActivityReportController::class,
            'store'
        ]
    );

    Route::get(
        '/activity-reports/{id}',
        [
            ActivityReportController::class,
            'show'
        ]
    );

    Route::patch(
        '/activity-reports/{id}/status',
        [
            ActivityReportController::class,
            'updateStatus'
        ]
    );
});