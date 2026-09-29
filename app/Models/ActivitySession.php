<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ActivitySession extends Model
{
    use HasFactory;

    protected $appends = [
        'start_image_url',
        'end_image_url',
    ];

    protected $fillable = [
        'assignment_id',
        'worker_id',

        'start_image_path',
        'start_latitude',
        'start_longitude',
        'start_location_accuracy',
        'start_device_timestamp',
        'started_at',

        'end_image_path',
        'end_latitude',
        'end_longitude',
        'end_location_accuracy',
        'end_device_timestamp',
        'completed_at',

        'expected_duration_minutes',
        'actual_duration_minutes',

        'remark',
        'status',

        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_latitude' => 'decimal:7',
            'start_longitude' => 'decimal:7',
            'start_location_accuracy' => 'decimal:2',

            'end_latitude' => 'decimal:7',
            'end_longitude' => 'decimal:7',
            'end_location_accuracy' => 'decimal:2',

            'start_device_timestamp' => 'datetime',
            'started_at' => 'datetime',

            'end_device_timestamp' => 'datetime',
            'completed_at' => 'datetime',

            'expected_duration_minutes' => 'integer',
            'actual_duration_minutes' => 'integer',

            'reviewed_at' => 'datetime',
        ];
    }

    public function getStartImageUrlAttribute(): ?string
    {
        return $this->start_image_path
            ? Storage::disk('public')->url($this->start_image_path)
            : null;
    }

    public function getEndImageUrlAttribute(): ?string
    {
        return $this->end_image_path
            ? Storage::disk('public')->url($this->end_image_path)
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment
    |--------------------------------------------------------------------------
    */

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            ProjectAssignment::class,
            'assignment_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Worker
    |--------------------------------------------------------------------------
    */

    public function worker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'worker_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reviewer
    |--------------------------------------------------------------------------
    */

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Session Updates
    |--------------------------------------------------------------------------
    */

    public function updates(): HasMany
    {
        return $this->hasMany(
            ActivityUpdate::class,
            'session_id'
        );
    }
}
