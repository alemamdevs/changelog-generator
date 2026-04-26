<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use App\Models\Concerns\LogsSuspiciousAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Changelog
 *
 * @property int $id
 * @property int|null $project_id
 * @property int|null $user_id
 * @property int $release_id
 * @property string $category
 * @property string $description
 * @property string|null $details
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Release $release
 */
class Changelog extends Model
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * Get the project associated with the changelog entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Scope changelog entries to a specific user.
     */
    public function scopeOwnedByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Resolve route binding scoped to the authenticated user.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $routeKey = $field ?? $this->getRouteKeyName();

        $query = $this->newQuery()->where($routeKey, $value);
        $changelog = $query->first();

        if ($changelog === null && auth()->check()) {
            $this->logSuspiciousAccess('tenant_model_not_found', $value);
        }

        return $changelog;
    }

    /**
     * Get the release that owns the changelog entry.
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id', 'id');
    }
}
