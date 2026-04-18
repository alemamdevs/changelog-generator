<?php

declare(strict_types=1);

use App\Models\Release;
use App\Models\WebhookDelivery;
use App\Services\Changelog\WebhookCommitProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function payloadWithCommit(string $hash): array
{
    return [
        'ref' => 'refs/heads/main',
        'repository' => [
            'full_name' => 'acme/changelog-generator',
        ],
        'commits' => [
            [
                'id' => $hash,
                'message' => 'fix(core): resolve flaky changelog parser',
                'timestamp' => now()->toIso8601String(),
                'author' => ['name' => 'Bob'],
            ],
        ],
    ];
}

test('prevents duplicate commit processing by hash and repository', function (): void {
    $service = app(WebhookCommitProcessingService::class);

    $firstDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'd-1',
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('hash-1'),
    ]);

    $firstRelease = $service->processDelivery($firstDelivery);

    expect($firstRelease)->toBeInstanceOf(Release::class)
        ->and(Release::query()->count())->toBe(1);

    $secondDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'd-2',
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('hash-1'),
    ]);

    $secondRelease = $service->processDelivery($secondDelivery);

    expect($secondRelease)->toBeNull()
        ->and($secondDelivery->fresh()?->status)->toBe('duplicate')
        ->and(Release::query()->count())->toBe(1);
});
