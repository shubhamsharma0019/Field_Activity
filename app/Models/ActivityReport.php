<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'worker_id',
        'report_type',
        'title',
        'description',
        'image_path',
        'latitude',
        'longitude',
        'location_accuracy',
        'status',
        'resolved_by',
        'resolution_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'location_accuracy' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            ProjectAssignment::class,
            'assignment_id'
        );
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'worker_id'
        );
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }
}