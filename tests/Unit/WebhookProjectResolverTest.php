<?php

declare(strict_types=1);

use App\Services\Security\WebhookProjectResolver;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

test('webhook project resolver caches github candidate lookups', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function (string $key, mixed $ttl, mixed $resolver): bool {
            return str_starts_with($key, 'webhook-project-candidates:github:') && is_callable($resolver);
        })
        ->andReturn(new EloquentCollection);

    app(WebhookProjectResolver::class)->githubCandidates('acme/changelog-generator');
});

test('webhook project resolver caches gitlab candidate lookups', function (): void {
    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function (string $key, mixed $ttl, mixed $resolver): bool {
            return str_starts_with($key, 'webhook-project-candidates:gitlab:') && is_callable($resolver);
        })
        ->andReturn(new EloquentCollection);

    app(WebhookProjectResolver::class)->gitlabCandidates('secret-1');
});
