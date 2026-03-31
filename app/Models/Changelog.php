<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Changelog
 *
 * @property int $id
 * @property int $release_id
 * @property string $category
 * @property string $description
 * @property string|null $details
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Release $release
 */
class Changelog extends Model
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    /**
     * Get the release that owns the changelog entry.
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id', 'id');
    }
}
