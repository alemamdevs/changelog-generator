<?php

declare(strict_types=1);

use App\Models\Release;
use App\Models\User;
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
