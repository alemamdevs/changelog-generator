<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('unauthorized tenant access is logged and hidden behind a 404', function (): void {
    Log::spy();

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Hidden Project',
        'github_repo' => 'hidden/project',
        'repository_full_name' => 'hidden/project',
        'webhook_secret' => 'secret-hidden',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.projects.show', $project))
        ->assertNotFound();

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Suspicious tenant access attempt'
            && ($context['model'] ?? null) === Project::class
            && ($context['reason'] ?? null) === 'tenant_model_not_found';
    });
});

test('webhook secrets are encrypted and hashed at rest', function (): void {
    $user = User::factory()->create();

    $project = Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Encrypted Project',
        'github_repo' => 'tenant/encrypted',
        'repository_full_name' => 'tenant/encrypted',
        'webhook_secret' => 'plain-secret-value',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $row = DB::table('projects')->where('id', $project->id)->first();

    expect($row)->not->toBeNull();
    expect($row?->webhook_secret_ciphertext)->not->toBe('plain-secret-value');
    expect($row?->webhook_secret_hash)->toBe(hash('sha256', 'plain-secret-value'));
    expect($project->fresh()->webhook_secret)->toBe('plain-secret-value');
});

test('webhook endpoint is rate limited', function (): void {
    $route = Route::getRoutes()->getByName('api.webhooks.push');

    expect($route)->not->toBeNull();
    expect($route?->gatherMiddleware())->toContain('throttle:webhook');
});

test('release access attempts from another tenant stay hidden', function (): void {
    Log::spy();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $project = Project::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Project',
        'github_repo' => 'tenant/other',
        'repository_full_name' => 'tenant/other',
        'webhook_secret' => 'secret-other',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $release = Release::query()->create([
        'project_id' => $project->id,
        'user_id' => $otherUser->id,
        'version' => 'v9.9.9',
        'branch' => 'main',
        'repository_full_name' => 'tenant/other',
        'generated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('admin.releases.show', $release))
        ->assertNotFound();

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Suspicious tenant access attempt'
            && ($context['model'] ?? null) === Release::class
            && ($context['reason'] ?? null) === 'tenant_model_not_found';
    });
});
