<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model representing a raw AI response for auditing and debugging.
 * Stored as raw_text and optional structured_json.
 */
final class AIResponse extends Model
{
    protected $table = 'ai_responses';

    protected $guarded = ['id'];

    protected $casts = [
        'structured_json' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
