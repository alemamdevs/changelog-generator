<?php

declare(strict_types=1);

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant-owned models are automatically scoped to the authenticated user', function (): void {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    $firstProject = Project::query()->create([
        'user_id' => $firstUser->id,
        'name' => 'First Project',
        'github_repo' => 'tenant-one/repo',
        'repository_full_name' => 'tenant-one/repo',
        'webhook_secret' => 'secret-one',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $secondProject = Project::query()->create([
        'user_id' => $secondUser->id,
        'name' => 'Second Project',
        'github_repo' => 'tenant-two/repo',
        'repository_full_name' => 'tenant-two/repo',
        'webhook_secret' => 'secret-two',
        'default_branch' => 'main',
        'is_active' => true,
    ]);

    $firstRelease = Release::query()->create([
        'project_id' => $firstProject->id,
        'user_id' => $firstUser->id,
        'version' => 'v1.0.0',
        'branch' => 'main',
        'generated_at' => now()->subDay(),
    ]);

    $secondRelease = Release::query()->create([
        'project_id' => $secondProject->id,
        'user_id' => $secondUser->id,
        'version' => 'v9.9.9',
        'branch' => 'main',
        'generated_at' => now(),
    ]);

    Commit::query()->create([
        'release_id' => $firstRelease->id,
        'project_id' => $firstProject->id,
        'user_id' => $firstUser->id,
        'commit_hash' => 'first-commit',
        'author' => 'First User',
        'message' => 'First release commit',
        'type' => 'feat',
        'source' => 'webhook',
        'authored_at' => now()->subHour(),
    ]);

    Commit::query()->create([
        'release_id' => $secondRelease->id,
        'project_id' => $secondProject->id,
        'user_id' => $secondUser->id,
        'commit_hash' => 'second-commit',
        'author' => 'Second User',
        'message' => 'Second release commit',
        'type' => 'fix',
        'source' => 'webhook',
        'authored_at' => now(),
    ]);

    Changelog::query()->create([
        'project_id' => $firstProject->id,
        'user_id' => $firstUser->id,
        'release_id' => $firstRelease->id,
        'category' => 'Added',
        'description' => 'First changelog entry',
        'details' => 'Visible to the first tenant only.',
        'position' => 1,
    ]);

    Changelog::query()->create([
        'project_id' => $secondProject->id,
        'user_id' => $secondUser->id,
        'release_id' => $secondRelease->id,
        'category' => 'Fixed',
        'description' => 'Second changelog entry',
        'details' => 'Visible to the second tenant only.',
        'position' => 1,
    ]);

    $this->actingAs($firstUser);

    expect(Project::query()->pluck('github_repo')->all())->toBe(['tenant-one/repo']);
    expect(Release::query()->pluck('version')->all())->toBe(['v1.0.0']);
    expect(Commit::query()->pluck('commit_hash')->all())->toBe(['first-commit']);
    expect(Changelog::query()->pluck('description')->all())->toBe(['First changelog entry']);
});
