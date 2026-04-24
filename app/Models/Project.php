<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    /**
     * Get the attributes that aren't mass assignable.
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get release history for the project.
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'repository_full_name', 'repository_full_name');
    }

    /**
     * Get the latest release for the project.
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(Release::class, 'repository_full_name', 'repository_full_name')->latestOfMany('generated_at');
    }
}
