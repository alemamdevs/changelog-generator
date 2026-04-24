<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('projects page shows setup project and existing projects', function (): void {
    Project::query()->create([
        'name' => 'Changelog Generator',
        'repository_full_name' => 'acme/changelog-generator',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $response = $this->get(route('admin.projects.index'));

    $response
        ->assertOk()
        ->assertSee('Projects')
        ->assertSee('Setup Project')
        ->assertSee('acme/changelog-generator');
});

test('project details page shows release history only for that project', function (): void {
    $project = Project::query()->create([
        'name' => 'Project A',
        'repository_full_name' => 'acme/project-a',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    Project::query()->create([
        'name' => 'Project B',
        'repository_full_name' => 'acme/project-b',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    Release::query()->create([
        'version' => 'v1.2.3',
        'branch' => 'main',
        'repository_full_name' => 'acme/project-a',
        'generated_at' => now()->subMinute(),
    ]);

    Release::query()->create([
        'version' => 'v9.9.9',
        'branch' => 'main',
        'repository_full_name' => 'acme/project-b',
        'generated_at' => now(),
    ]);

    $response = $this->get(route('admin.projects.show', $project));

    $response
        ->assertOk()
        ->assertSee('Release History')
        ->assertSee('v1.2.3')
        ->assertDontSee('v9.9.9');
});
