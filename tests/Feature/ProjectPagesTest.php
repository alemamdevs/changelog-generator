<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('projects page shows setup project and existing projects', function (): void {
    $user = User::factory()->create();

    Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Changelog Generator',
        'github_repo' => 'acme/changelog-generator',
        'repository_full_name' => 'acme/changelog-generator',
        'webhook_secret' => 'secret-1',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('admin.projects.index'));

    $response
        ->assertOk()
        ->assertSee('Projects')
        ->assertSee('Setup Project')
        ->assertSee('acme/changelog-generator');
});

test('project details page shows release history only for that project', function (): void {
    $user = User::factory()->create();

    $project = Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Project A',
        'github_repo' => 'acme/project-a',
        'repository_full_name' => 'acme/project-a',
        'webhook_secret' => 'secret-2',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    Project::query()->create([
        'user_id' => $user->id,
        'name' => 'Project B',
        'github_repo' => 'acme/project-b',
        'repository_full_name' => 'acme/project-b',
        'webhook_secret' => 'secret-3',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    Release::query()->create([
        'user_id' => $user->id,
        'version' => 'v1.2.3',
        'branch' => 'main',
        'repository_full_name' => 'acme/project-a',
        'generated_at' => now()->subMinute(),
    ]);

    Release::query()->create([
        'user_id' => $user->id,
        'version' => 'v9.9.9',
        'branch' => 'main',
        'repository_full_name' => 'acme/project-b',
        'generated_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('admin.projects.show', $project));

    $response
        ->assertOk()
        ->assertSee('Release History')
        ->assertSee('v1.2.3')
        ->assertDontSee('v9.9.9');
});
