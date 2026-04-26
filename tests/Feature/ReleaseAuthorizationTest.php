<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('release index only shows authenticated user releases', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Release::query()->create([
        'user_id' => $user->id,
        'version' => 'v1.0.0',
        'branch' => 'main',
        'repository_full_name' => 'tenant-a/repo',
        'generated_at' => now()->subDay(),
    ]);

    Release::query()->create([
        'user_id' => $otherUser->id,
        'version' => 'v9.9.9',
        'branch' => 'main',
        'repository_full_name' => 'tenant-b/repo',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.releases.index'))
        ->assertOk()
        ->assertSee('v1.0.0')
        ->assertDontSee('v9.9.9');
});

test('release show does not leak by id across users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherRelease = Release::query()->create([
        'user_id' => $otherUser->id,
        'version' => 'v9.0.0',
        'branch' => 'main',
        'repository_full_name' => 'tenant-b/repo',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.releases.show', $otherRelease))
        ->assertNotFound();
});

test('webhook deliveries are scoped to the authenticated user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $userProject = Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Tenant A',
        'github_repo' => 'tenant-a/repo',
        'repository_full_name' => 'tenant-a/repo',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-a',
        'is_active' => true,
    ]);

    $otherProject = Project::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Tenant B',
        'github_repo' => 'tenant-b/repo',
        'repository_full_name' => 'tenant-b/repo',
        'default_branch' => 'main',
        'webhook_secret' => 'secret-b',
        'is_active' => true,
    ]);

    WebhookDelivery::query()->create([
        'project_id' => $userProject->id,
        'user_id' => $user->id,
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'delivery-1',
        'repository_full_name' => 'tenant-a/repo',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => [],
    ]);

    $otherDelivery = WebhookDelivery::query()->create([
        'project_id' => $otherProject->id,
        'user_id' => $otherUser->id,
        'provider' => 'github',
        'event' => 'push',
        'delivery_id' => 'delivery-2',
        'repository_full_name' => 'tenant-b/repo',
        'ref' => 'refs/heads/main',
        'signature_valid' => true,
        'status' => 'queued',
        'payload' => [],
    ]);

    $this->actingAs($user)
        ->get(route('admin.webhooks.index'))
        ->assertOk()
        ->assertSee('tenant-a/repo')
        ->assertDontSee('tenant-b/repo');

    $this->actingAs($user)
        ->get(route('admin.webhooks.show', $otherDelivery))
        ->assertNotFound();
});
