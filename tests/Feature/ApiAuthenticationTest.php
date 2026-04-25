<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register and receive sanctum token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'pest-test',
    ]);

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'token',
            'token_type',
            'user' => ['id', 'name', 'email'],
        ]);
});

test('user can login and logout using sanctum token', function (): void {
    $user = User::query()->create([
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
        'device_name' => 'pest-test',
    ]);

    $login->assertOk()->assertJsonStructure(['token', 'token_type', 'user']);

    $token = (string) $login->json('token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertOk();
});

test('projects routes are protected and scoped to authenticated user', function (): void {
    $firstUser = User::query()->create([
        'name' => 'User One',
        'email' => 'one@example.com',
        'password' => 'password123',
    ]);

    $secondUser = User::query()->create([
        'name' => 'User Two',
        'email' => 'two@example.com',
        'password' => 'password123',
    ]);

    Project::query()->create([
        'user_id' => $firstUser->id,
        'name' => 'First Project',
        'github_repo' => 'tenant-one/repo',
        'repository_full_name' => 'tenant-one/repo',
        'webhook_secret' => 'secret-tenant-one',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    Project::query()->create([
        'user_id' => $secondUser->id,
        'name' => 'Second Project',
        'github_repo' => 'tenant-two/repo',
        'repository_full_name' => 'tenant-two/repo',
        'webhook_secret' => 'secret-tenant-two',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $this->getJson('/api/projects')->assertUnauthorized();

    $token = $firstUser->createToken('pest')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/projects')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('github_repo')->all())
        ->toContain('tenant-one/repo')
        ->not->toContain('tenant-two/repo');
});

test('api project show does not leak by id across users', function (): void {
    $owner = User::query()->create([
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => 'password123',
    ]);

    $otherUser = User::query()->create([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => 'password123',
    ]);

    $project = Project::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Project',
        'github_repo' => 'other/repo',
        'repository_full_name' => 'other/repo',
        'webhook_secret' => 'secret-other',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $token = $owner->createToken('pest')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/projects/'.$project->id)
        ->assertNotFound();
});
