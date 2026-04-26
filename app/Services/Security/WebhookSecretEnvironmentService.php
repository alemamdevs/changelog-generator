<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class WebhookSecretEnvironmentService
{
    private const GITHUB_KEY = 'GITHUB_WEBHOOK_SECRET';

    private const GITLAB_KEY = 'GITLAB_WEBHOOK_SECRET';

    public function ensureMissingSecrets(string $provider): array
    {
        $targets = match ($provider) {
            'github' => [self::GITHUB_KEY],
            'gitlab' => [self::GITLAB_KEY],
            default => [self::GITHUB_KEY, self::GITLAB_KEY],
        };

        $envPath = (string) config('changelog.webhooks.env_file', base_path('.env'));
        $contents = File::exists($envPath) ? (string) File::get($envPath) : '';
        $updatedContents = $contents;
        $generated = [];

        foreach ($targets as $key) {
            $existingValue = $this->readEnvValue($updatedContents, $key);

            if ($existingValue !== null && trim($existingValue) !== '') {
                continue;
            }

            $secret = Str::random(64);
            $updatedContents = $this->upsertEnvValue($updatedContents, $key, $secret);
            $generated[$key] = $secret;
        }

        if ($updatedContents !== $contents) {
            File::put($envPath, $updatedContents);
        }

        return $generated;
    }

    private function readEnvValue(string $contents, string $key): ?string
    {
        $pattern = '/^'.preg_quote($key, '/').'=(.*)$/m';

        if (! preg_match($pattern, $contents, $matches)) {
            return null;
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private function upsertEnvValue(string $contents, string $key, string $value): string
    {
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $line, $contents, 1);
        }

        if ($contents !== '' && ! str_ends_with($contents, PHP_EOL)) {
            return $contents.PHP_EOL.$line.PHP_EOL;
        }

        return $contents.$line.PHP_EOL;
    }
}
