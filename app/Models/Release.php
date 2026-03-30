<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Release
 *
 * @property int $id
 * @property string $version
 * @property string $branch
 * @property Carbon $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|Commit[] $commits
 * @property-read \Illuminate\Database\Eloquent\Collection|Changelog[] $changelogs
 */
class Release extends Model
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
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
