<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'password',
        'role',
        'company_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Projects Created By User
    |--------------------------------------------------------------------------
    */

    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Worker Assignments
    |--------------------------------------------------------------------------
    */

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'worker_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Assignments Created By Admin
    |--------------------------------------------------------------------------
    */

    public function assignedProjects(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'assigned_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Submissions
    |--------------------------------------------------------------------------
    */

    public function activitySubmissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class, 'worker_id');
    }

    public function reviewedSubmissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class, 'reviewed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Sessions
    |--------------------------------------------------------------------------
    */

    public function activitySessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'worker_id');
    }

    public function reviewedSessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'reviewed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Updates
    |--------------------------------------------------------------------------
    */

    public function activityUpdates(): HasMany
    {
        return $this->hasMany(ActivityUpdate::class, 'worker_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Worker Locations
    |--------------------------------------------------------------------------
    */

    public function workerLocations(): HasMany
    {
        return $this->hasMany(WorkerLocation::class, 'worker_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Reports
    |--------------------------------------------------------------------------
    */

    public function activityReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class, 'worker_id');
    }

    public function resolvedReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class, 'resolved_by');
    }
}