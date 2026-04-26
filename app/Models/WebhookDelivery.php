<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WebhookDelivery extends Model
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
            'project_id' => 'integer',
            'user_id' => 'integer',
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this webhook delivery.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the project associated with this webhook delivery.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Get the release attached to this webhook processing result.
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id', 'id');
    }
}
