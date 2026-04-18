<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Commit
 *
 * @property int $id
 * @property int $release_id
 * @property string|null $repository_full_name
 * @property string $commit_hash
 * @property string|null $author
 * @property string|null $message
 * @property string|null $type
 * @property string|null $scope
 * @property string|null $category
 * @property bool $is_breaking
 * @property string|null $subject
 * @property string|null $body
 * @property string $source
 * @property Carbon|null $authored_at
 * @property Carbon|null $committed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Release $release
 */
class Commit extends Model
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
            'is_breaking' => 'boolean',
            'authored_at' => 'datetime',
            'committed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the release that owns the commit.
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id', 'id');
    }
}
