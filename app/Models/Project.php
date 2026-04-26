<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUserScope;
use App\Models\Concerns\LogsSuspiciousAccess;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class Project extends Model
{
    use HasUserScope;
    use LogsSuspiciousAccess;

    /**
     * Get the attributes that aren't mass assignable.
     */
    protected $guarded = ['id'];

    /**
     * Hide internal webhook secret storage columns.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'webhook_secret_ciphertext',
        'webhook_secret_hash',
    ];

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
     * Store webhook secrets encrypted and keep a searchable hash.
     */
    protected function webhookSecret(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes): ?string {
                $ciphertext = $attributes['webhook_secret_ciphertext'] ?? null;

                if ($ciphertext === null || $ciphertext === '') {
                    return null;
                }

                return Crypt::decryptString($ciphertext);
            },
            set: function (?string $value): array {
                if ($value === null || $value === '') {
                    return [
                        'webhook_secret_ciphertext' => null,
                        'webhook_secret_hash' => null,
                    ];
                }

                return [
                    'webhook_secret_ciphertext' => Crypt::encryptString($value),
                    'webhook_secret_hash' => hash('sha256', $value),
                ];
            }
        );
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
        return $this->hasMany(Release::class, 'project_id', 'id')
            ->where('user_id', $this->user_id);
    }

    /**
     * Get the latest release for the project.
     */
    public function latestRelease(): HasOne
    {
        return $this->hasOne(Release::class, 'project_id', 'id')
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
        $project = $query->first();

        if ($project === null && auth()->check()) {
            $this->logSuspiciousAccess('tenant_model_not_found', $value);
        }

        return $project;
    }
}
