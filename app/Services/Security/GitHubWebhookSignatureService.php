<?php

declare(strict_types=1);

namespace App\Services\Security;

final class GitHubWebhookSignatureService
{
    public function isValid(string $rawPayload, ?string $signatureHeader): bool
    {
        $secret = (string) config('services.github.webhook_secret', '');

        if ($secret === '' || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawPayload, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
