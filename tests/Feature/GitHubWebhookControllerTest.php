<?php

declare(strict_types=1);

use App\Jobs\ProcessGitHubPushWebhookJob;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function githubPayload(): array
{
    return [
        'ref' => 'refs/heads/main',
        'repository' => [
            'full_name' => 'acme/changelog-generator',
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

test('accepts signed github push webhook and queues processing', function (): void {
    Queue::fake();

    config()->set('services.github.webhook_secret', 'secret-token');

    $payload = githubPayload();
    $raw = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', (string) $raw, 'secret-token');

    $response = $this
        ->withHeaders([
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'delivery-1',
            'X-Hub-Signature-256' => $signature,
        ])
        ->postJson('/api/webhooks/push', $payload);

    $response->assertAccepted();

    Queue::assertPushed(ProcessGitHubPushWebhookJob::class);

    expect(WebhookDelivery::query()->count())->toBe(1)
        ->and(WebhookDelivery::query()->first()?->signature_valid)->toBeTrue();
});

test('rejects invalid webhook signature', function (): void {
    Queue::fake();

    config()->set('services.github.webhook_secret', 'secret-token');

    $payload = githubPayload();

    $response = $this
        ->withHeaders([
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=invalid',
        ])
        ->postJson('/api/webhooks/push', $payload);

    $response->assertStatus(401);

    Queue::assertNothingPushed();
    expect(WebhookDelivery::query()->count())->toBe(0);
});
