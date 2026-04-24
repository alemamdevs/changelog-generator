<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
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
            'major' => 'integer',
            'minor' => 'integer',
            'patch' => 'integer',
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

    /**
     * Get processed commits attached to this release.
     */
    public function processedCommits(): HasMany
    {
        return $this->hasMany(ProcessedCommit::class, 'release_id', 'id');
    }

    /**
     * Get webhook deliveries that generated this release.
     */
    public function webhookDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'release_id', 'id');
    }

    /**
     * Get the project this release belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'repository_full_name', 'repository_full_name');
    }
}
