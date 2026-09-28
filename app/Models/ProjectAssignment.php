<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_activity_id',
        'worker_id',
        'assigned_by',
        'target_quantity',
        'completed_quantity',
        'tracking_required',
        'assigned_date',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_quantity' => 'integer',
            'completed_quantity' => 'integer',
            'tracking_required' => 'boolean',
            'assigned_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectActivity(): BelongsTo
    {
        return $this->belongsTo(ProjectActivity::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ActivitySubmission::class, 'assignment_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'assignment_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(WorkerLocation::class, 'assignment_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ActivityReport::class, 'assignment_id');
    }
}