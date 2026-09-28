<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'worker_id',
        'submission_no',
        'image_path',
        'latitude',
        'longitude',
        'location_accuracy',
        'device_timestamp',
        'server_timestamp',
        'remark',
        'approval_status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'submission_no' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'device_timestamp' => 'datetime',
            'server_timestamp' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
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
}