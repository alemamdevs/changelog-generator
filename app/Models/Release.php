<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use App\Models\Concerns\LogsSuspiciousAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    use HasUserScope;
    use LogsSuspiciousAccess;

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
            'project_id' => 'integer',
            'user_id' => 'integer',
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
        return $this->hasMany(Commit::class, 'release_id', 'id')
            ->orderBy('authored_at');
    }

    /**
     * Get the changelog entries for the release.
     */
    public function changelogs(): HasMany
    {
        return $this->hasMany(Changelog::class, 'release_id', 'id')
            ->orderBy('position');
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
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Resolve route binding scoped to the authenticated user.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $routeKey = $field ?? $this->getRouteKeyName();

        $query = $this->newQuery()->where($routeKey, $value);
        $release = $query->first();

        if ($release === null && auth()->check()) {
            $this->logSuspiciousAccess('tenant_model_not_found', $value);
        }

        return $release;
    }
}
