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
 * @property string $commit_hash
 * @property string|null $author
 * @property string|null $message
 * @property string|null $type
 * @property Carbon|null $authored_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Release $release
 */
class Commit extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int,string>
     */
    protected array $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string,string>
     */
    protected array $casts = [
        'authored_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the release that owns the commit.
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id', 'id');
    }
}
