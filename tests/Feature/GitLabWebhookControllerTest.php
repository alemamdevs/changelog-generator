<?php

declare(strict_types=1);

use App\Jobs\ProcessGitHubPushWebhookJob;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function gitlabPayload(): array
{
    return [
        'ref' => 'refs/heads/main',
        'project' => [
            'path_with_namespace' => 'acme/changelog-generator',
        ],
        'commits' => [
            [
                'id' => 'abc123def456',
                'message' => 'feat(api): add release endpoint',
                'timestamp' => now()->toIso8601String(),
                'author' => ['name' => 'Alice'],
            ],
        ],
    ];
}

test('accepts valid gitlab push webhook and queues processing', function (): void {
    Queue::fake();

    config()->set('services.gitlab.webhook_secret', 'gitlab-secret-token');

    $response = $this
        ->withHeaders([
            'X-Gitlab-Event' => 'Push Hook',
            'X-Gitlab-Event-UUID' => 'uuid-1',
            'X-Gitlab-Token' => 'gitlab-secret-token',
        ])
        ->postJson('/api/webhooks/push', gitlabPayload());

    $response->assertAccepted();

    Queue::assertPushed(ProcessGitHubPushWebhookJob::class);

    expect(WebhookDelivery::query()->count())->toBe(1)
        ->and(WebhookDelivery::query()->first()?->provider)->toBe('gitlab')
        ->and(WebhookDelivery::query()->first()?->signature_valid)->toBeTrue();
});

test('rejects gitlab webhook with invalid token', function (): void {
    Queue::fake();

    config()->set('services.gitlab.webhook_secret', 'gitlab-secret-token');

    $response = $this
        ->withHeaders([
            'X-Gitlab-Event' => 'Push Hook',
            'X-Gitlab-Token' => 'invalid',
        ])
        ->postJson('/api/webhooks/push', gitlabPayload());

    $response->assertStatus(401);

    Queue::assertNothingPushed();
    expect(WebhookDelivery::query()->count())->toBe(0);
});
