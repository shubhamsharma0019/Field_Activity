<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'activity_mode',
        'tracking_required',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tracking_required' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Project Activities
    |--------------------------------------------------------------------------
    |
    | One activity type can be used in multiple project activities.
    |
    */

    public function projectActivities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }
}