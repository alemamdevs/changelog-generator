<?php

declare(strict_types=1);

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Changelog\WebhookCommitProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWebhookProject(User $user, string $repositoryFullName, string $webhookSecret): Project
{
    return Project::query()->create([
        'user_id' => $user->id,
        'name' => $repositoryFullName,
        'github_repo' => $repositoryFullName,
        'repository_full_name' => $repositoryFullName,
        'default_branch' => 'main',
        'webhook_secret' => $webhookSecret,
        'is_active' => true,
    ]);
}

function payloadWithCommit(string $repositoryFullName, string $hash): array
{
    return [
        'ref' => 'refs/heads/main',
        'repository' => [
            'full_name' => $repositoryFullName,
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
    $user = User::factory()->create();
    createWebhookProject($user, 'acme/changelog-generator', 'secret-1');

    $service = app(WebhookCommitProcessingService::class);

    $firstProjectId = Project::query()
        ->where('user_id', $user->id)
        ->where('github_repo', 'acme/changelog-generator')
        ->value('id');

    $firstDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'd-1',
        'project_id' => $firstProjectId,
        'user_id' => $user->id,
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('acme/changelog-generator', 'hash-1'),
    ]);

    $firstRelease = $service->processDelivery($firstDelivery, (int) $firstProjectId, $user->id);

    expect($firstRelease)->toBeInstanceOf(Release::class)
        ->and(Release::query()->count())->toBe(1);

    expect(Release::query()->first()?->project_id)->toBe($firstProjectId)
        ->and(Commit::query()->first()?->project_id)->toBe($firstProjectId)
        ->and(Changelog::query()->first()?->project_id)->toBe($firstProjectId)
        ->and(Changelog::query()->first()?->user_id)->toBe($user->id);

    $secondDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'd-2',
        'project_id' => $firstProjectId,
        'user_id' => $user->id,
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('acme/changelog-generator', 'hash-1'),
    ]);

    $secondRelease = $service->processDelivery($secondDelivery, $firstProjectId, $user->id);

    expect($secondRelease)->toBeNull()
        ->and($secondDelivery->fresh()?->status)->toBe('duplicate')
        ->and(Release::query()->count())->toBe(1)
        ->and(Commit::query()->count())->toBe(1)
        ->and(Changelog::query()->count())->toBe(1);
});

test('processes the same repository independently for different tenants', function (): void {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    createWebhookProject($firstUser, 'acme/changelog-generator', 'secret-1');
    createWebhookProject($secondUser, 'acme/changelog-generator', 'secret-2');

    $service = app(WebhookCommitProcessingService::class);

    $firstDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'tenant-1',
        'project_id' => Project::query()->where('user_id', $firstUser->id)->where('github_repo', 'acme/changelog-generator')->value('id'),
        'user_id' => $firstUser->id,
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('acme/changelog-generator', 'shared-hash'),
    ]);

    $secondDelivery = WebhookDelivery::query()->create([
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'tenant-2',
        'project_id' => Project::query()->where('user_id', $secondUser->id)->where('github_repo', 'acme/changelog-generator')->value('id'),
        'user_id' => $secondUser->id,
        'repository_full_name' => 'acme/changelog-generator',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => payloadWithCommit('acme/changelog-generator', 'shared-hash'),
    ]);

    $firstRelease = $service->processDelivery($firstDelivery, (int) $firstDelivery->project_id, $firstUser->id);
    $secondRelease = $service->processDelivery($secondDelivery, (int) $secondDelivery->project_id, $secondUser->id);

    expect($firstRelease)->toBeInstanceOf(Release::class)
        ->and($secondRelease)->toBeInstanceOf(Release::class)
        ->and(Release::query()->count())->toBe(2)
        ->and($firstDelivery->fresh()?->status)->toBe('processed')
        ->and($secondDelivery->fresh()?->status)->toBe('processed')
        ->and(Commit::query()->count())->toBe(2);
});
