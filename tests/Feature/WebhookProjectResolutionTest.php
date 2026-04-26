<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function signedGitHubPayload(array $payload, string $secret): array
{
    $rawPayload = json_encode($payload);

    return [
        'headers' => [
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => 'delivery-1',
            'X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $rawPayload, $secret),
        ],
    ];
}

function webhookPayload(string $repositoryFullName, string $commitHash): array
{
    return [
        'ref' => 'refs/heads/main',
        'repository' => [
            'full_name' => $repositoryFullName,
        ],
        'commits' => [
            [
                'id' => $commitHash,
                'message' => 'fix(core): keep webhook routing isolated',
                'timestamp' => now()->toIso8601String(),
                'author' => ['name' => 'Alice'],
            ],
        ],
    ];
}

test('queues github webhooks for the matching tenant project', function (): void {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    Project::query()->create([
        'user_id' => $firstUser->id,
        'name' => 'First Project',
        'github_repo' => 'acme/changelog-generator',
        'repository_full_name' => 'acme/changelog-generator',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-1',
        'is_active' => true,
    ]);

    Project::query()->create([
        'user_id' => $secondUser->id,
        'name' => 'Second Project',
        'github_repo' => 'acme/changelog-generator',
        'repository_full_name' => 'acme/changelog-generator',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-2',
        'is_active' => true,
    ]);

    $payload = webhookPayload('acme/changelog-generator', 'hash-1');
    $signedPayload = signedGitHubPayload($payload, 'secret-2');

    $response = $this->postJson(route('api.webhooks.push'), $payload, $signedPayload['headers']);

    $response->assertAccepted();

    expect(WebhookDelivery::query()->count())->toBe(1)
        ->and(WebhookDelivery::query()->first()?->project_id)->toBe(Project::query()->where('user_id', $secondUser->id)->value('id'))
        ->and(WebhookDelivery::query()->first()?->user_id)->toBe($secondUser->id)
        ->and(WebhookDelivery::query()->first()?->repository_full_name)->toBe('acme/changelog-generator');
});

test('rejects github webhooks with invalid signatures', function (): void {
    $user = User::factory()->create();

    Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Webhooks',
        'github_repo' => 'acme/changelog-generator',
        'repository_full_name' => 'acme/changelog-generator',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-1',
        'is_active' => true,
    ]);

    $payload = webhookPayload('acme/changelog-generator', 'hash-1');
    $signedPayload = signedGitHubPayload($payload, 'wrong-secret');

    $response = $this->postJson(route('api.webhooks.push'), $payload, $signedPayload['headers']);

    $response->assertUnauthorized();

    expect(WebhookDelivery::query()->count())->toBe(0);
});

test('rejects github webhooks for unknown repositories', function (): void {
    $user = User::factory()->create();

    Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Webhooks',
        'github_repo' => 'acme/another-repo',
        'repository_full_name' => 'acme/another-repo',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-1',
        'is_active' => true,
    ]);

    $payload = webhookPayload('acme/changelog-generator', 'hash-1');
    $signedPayload = signedGitHubPayload($payload, 'secret-1');

    $response = $this->postJson(route('api.webhooks.push'), $payload, $signedPayload['headers']);

    $response->assertNotFound();

    expect(WebhookDelivery::query()->count())->toBe(0);
});

test('queues gitlab webhooks using the project webhook secret', function (): void {
    $user = User::factory()->create();

    Project::query()->create([
        'user_id' => $user->id,
        'name' => 'GitLab Project',
        'github_repo' => 'acme/changelog-generator',
        'repository_full_name' => 'acme/changelog-generator',
        'default_branch' => 'main',
        'webhook_secret' => 'gitlab-secret-1',
        'is_active' => true,
    ]);

    $payload = [
        'ref' => 'refs/heads/main',
        'project' => [
            'path_with_namespace' => 'acme/changelog-generator',
        ],
        'commits' => [
            [
                'id' => 'hash-1',
                'message' => 'fix(core): keep webhook routing isolated',
                'timestamp' => now()->toIso8601String(),
                'author' => ['name' => 'Alice'],
            ],
        ],
    ];

    $response = $this->postJson(route('api.webhooks.push'), $payload, [
        'X-Gitlab-Event' => 'Push Hook',
        'X-Gitlab-Event-UUID' => 'gitlab-delivery-1',
        'X-Gitlab-Token' => 'gitlab-secret-1',
    ]);

    $response->assertAccepted();

    expect(WebhookDelivery::query()->count())->toBe(1)
        ->and(WebhookDelivery::query()->first()?->project_id)->toBe(Project::query()->where('user_id', $user->id)->value('id'))
        ->and(WebhookDelivery::query()->first()?->user_id)->toBe($user->id);
});
