<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_code',
        'company_id',
        'name',
        'description',
        'project_type',
        'start_date',
        'end_date',
        'state',
        'city',
        'area',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
    | Project Creator
    |--------------------------------------------------------------------------
    |
    | Admin / Super Admin who created the project.
    |
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Project Activities
    |--------------------------------------------------------------------------
    */

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Project Assignments
    |--------------------------------------------------------------------------
    */

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }
}