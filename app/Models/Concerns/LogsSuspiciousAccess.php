<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Log;

trait LogsSuspiciousAccess
{
    /**
     * Log a suspicious access attempt without exposing sensitive data.
     */
    protected function logSuspiciousAccess(string $reason, mixed $value = null): void
    {
        Log::warning('Suspicious tenant access attempt', [
            'reason' => $reason,
            'model' => static::class,
            'value' => $value,
            'user_id' => auth()->id(),
            'route_name' => request()?->route()?->getName(),
            'path' => request()?->path(),
            'ip' => request()?->ip(),
        ]);
    }
}
