<?php

declare(strict_types=1);

namespace App\Services\Security;

final class GitLabWebhookTokenService
{
    public function isValid(?string $tokenHeader): bool
    {
        $secret = (string) config('services.gitlab.webhook_secret', '');

        if ($secret === '' || $tokenHeader === null || $tokenHeader === '') {
            return false;
        }

        return hash_equals($secret, $tokenHeader);
    }
}
