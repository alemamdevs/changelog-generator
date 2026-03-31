<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Release extends Model
{
    /**
     * Get the attributes that aren't mass assignable.
     */
    protected function guarded(): array
    {
        return ['id'];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the commits for the release.
     */
    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class, 'release_id', 'id')->orderBy('authored_at');
    }

    /**
     * Get the changelog entries for the release.
     */
    public function changelogs(): HasMany
    {
        return $this->hasMany(Changelog::class, 'release_id', 'id')->orderBy('position');
    }
}
