<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'activity_type_id',
        'name',
        'target_quantity',
        'expected_duration_minutes',
        'instructions',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_quantity' => 'integer',
            'expected_duration_minutes' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Type
    |--------------------------------------------------------------------------
    */

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Worker Assignments
    |--------------------------------------------------------------------------
    */

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }
}