<?php

declare(strict_types=1);

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\ProcessedCommit;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Models\WebhookDelivery;
use Database\Seeders\ChangelogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('changelog seeder creates multi user project release and commit histories', function (): void {
    $this->seed(ChangelogSeeder::class);

    expect(User::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(Project::query()->count())->toBeGreaterThanOrEqual(12)
        ->and(Release::query()->count())->toBeGreaterThanOrEqual(48)
        ->and(Commit::query()->count())->toBeGreaterThanOrEqual(300)
        ->and(Changelog::query()->count())->toBeGreaterThanOrEqual(300)
        ->and(ProcessedCommit::query()->count())->toBe(Commit::query()->count())
        ->and(WebhookDelivery::query()->count())->toBe(Release::query()->count());

    $usersWithProjects = User::query()->has('projects')->withCount('projects')->get();

    expect($usersWithProjects->count())->toBeGreaterThanOrEqual(6)
        ->and($usersWithProjects->every(static fn (User $user): bool => $user->projects_count >= 2))->toBeTrue();

    $projectsWithReleaseDepth = Release::query()
        ->selectRaw('project_id, COUNT(*) as release_count')
        ->groupBy('project_id')
        ->havingRaw('COUNT(*) >= 4')
        ->count();

    expect($projectsWithReleaseDepth)->toBeGreaterThanOrEqual(12);

    $release = Release::query()->with(['commits', 'changelogs'])->first();

    expect($release)->not->toBeNull()
        ->and($release?->commits->count())->toBeGreaterThanOrEqual(7)
        ->and($release?->changelogs->count())->toBeGreaterThanOrEqual(7);
});
