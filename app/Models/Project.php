<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'user_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Scope to projects owned by a specific user.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the user that owns the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get release history for the project.
     */
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'repository_full_name', 'github_repo')
            ->where('user_id', $this->user_id);
    }

    /**
     * Get the latest release for the project.
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(Release::class, 'repository_full_name', 'github_repo')
            ->where('user_id', $this->user_id)
            ->latestOfMany('generated_at');
    }

    /**
     * Resolve route binding scoped to the authenticated user.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $routeKey = $field ?? $this->getRouteKeyName();

        $query = $this->newQuery()->where($routeKey, $value);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        }

        return $query->first();
    }
}
