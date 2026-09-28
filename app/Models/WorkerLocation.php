<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'worker_id',
        'assignment_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'decimal:2',
            'speed' => 'decimal:2',
            'heading' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
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
}