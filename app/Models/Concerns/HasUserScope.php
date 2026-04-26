<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasUserScope
{
    /**
     * Boot the user global scope.
     */
    protected static function bootHasUserScope(): void
    {
        static::addGlobalScope('user', function (Builder $builder): void {
            $userId = auth()->id();

            if ($userId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('user_id'), $userId);
        });
    }

    /**
     * Scope the query to a specific user.
     */
    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->withoutGlobalScope('user')->where('user_id', $userId);
    }
}
