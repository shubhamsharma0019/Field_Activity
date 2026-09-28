<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_code',
        'name',
        'contact_person',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Company Users
    |--------------------------------------------------------------------------
    |
    | Company login users associated with this company.
    |
    */

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Company Projects
    |--------------------------------------------------------------------------
    |
    | Only company projects have company_id.
    | Internal/Admin projects have company_id = NULL.
    |
    */

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}