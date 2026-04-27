<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CommitCategory;
use App\Models\Changelog;
use App\Models\Commit;
use App\Models\ProcessedCommit;
use App\Models\Project;
use App\Models\Release;
use App\Models\User;
use App\Models\WebhookDelivery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChangelogSeeder extends Seeder
{
    /**
     * Seed realistic preview data for dashboard pages.
     */
    public function run(): void
    {
        $faker = fake();
        $faker->seed(27042026);

        $baseUsers = collect([
            User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            ),
            User::query()->firstOrCreate(
                ['email' => 'devlead@example.com'],
                [
                    'name' => 'Dev Lead',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            ),
        ]);

        $users = $baseUsers
            ->merge(User::factory()->count(4)->create())
            ->values();

        $projectNames = [
            'API Gateway',
            'Billing Engine',
            'Notification Hub',
            'Admin Console',
            'Support Desk',
            'Analytics Core',
            'Developer Portal',
            'Mobile Backend',
        ];

        $categoryMap = [
            'feat' => CommitCategory::Feature,
            'fix' => CommitCategory::Fix,
            'refactor' => CommitCategory::Refactor,
            'docs' => CommitCategory::Docs,
            'chore' => CommitCategory::Chore,
            'perf' => CommitCategory::Feature,
        ];

        foreach ($users as $userIndex => $user) {
            $projectCount = 2 + ($userIndex % 3);

            for ($projectNumber = 1; $projectNumber <= $projectCount; $projectNumber++) {
                $projectName = $projectNames[($userIndex + $projectNumber - 1) % count($projectNames)];
                $organizationSlug = Str::slug(Str::before($user->email, '@'));
                $repositorySlug = Str::slug($projectName).'-'.$projectNumber;
                $repository = $organizationSlug.'/'.$repositorySlug;
                $defaultBranch = $projectNumber % 3 === 0 ? 'develop' : 'main';

                $project = Project::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'github_repo' => $repository,
                    ],
                    [
                        'name' => $projectName,
                        'repository_full_name' => $repository,
                        'default_branch' => $defaultBranch,
                        'webhook_secret' => hash('sha256', $repository.'|secret'),
                        'is_active' => true,
                    ],
                );

                $releaseCount = 4 + (($userIndex + $projectNumber) % 3);
                $major = 1;
                $minor = 0;
                $patch = 0;

                for ($releaseNumber = 1; $releaseNumber <= $releaseCount; $releaseNumber++) {
                    if ($releaseNumber % 5 === 0) {
                        $major++;
                        $minor = 0;
                        $patch = 0;
                    } elseif ($releaseNumber % 2 === 0) {
                        $minor++;
                        $patch = 0;
                    } else {
                        $patch++;
                    }

                    $version = sprintf('v%d.%d.%d', $major, $minor, $patch);
                    $generatedAt = now()->subDays((8 - $releaseNumber) * 4 + $projectNumber + $userIndex);

                    $release = Release::query()->updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'version' => $version,
                        ],
                        [
                            'user_id' => $user->id,
                            'major' => $major,
                            'minor' => $minor,
                            'patch' => $patch,
                            'tag_name' => $version,
                            'branch' => $project->default_branch,
                            'repository_full_name' => $project->github_repo,
                            'generated_at' => $generatedAt,
                            'markdown_path' => sprintf(
                                'changelogs/%s/CHANGELOG-%s.md',
                                str_replace('/', '-', $project->github_repo),
                                $version,
                            ),
                        ],
                    );

                    $release->commits()->delete();
                    $release->changelogs()->delete();
                    ProcessedCommit::query()->where('release_id', $release->id)->delete();
                    WebhookDelivery::query()->where('release_id', $release->id)->delete();

                    $commitCount = 7 + (($releaseNumber + $projectNumber) % 6);
                    $entriesByHeading = [];
                    $payloadCommits = [];
                    $types = array_keys($categoryMap);

                    for ($commitIndex = 1; $commitIndex <= $commitCount; $commitIndex++) {
                        $type = $types[($commitIndex + $releaseNumber + $projectNumber) % count($types)];
                        $category = $categoryMap[$type];
                        $scope = ['api', 'ui', 'queue', 'security', 'release', 'infra'][($commitIndex + $projectNumber) % 6];
                        $subject = Str::of($faker->sentence(5))->lower()->rtrim('.')->value();
                        $isBreaking = $releaseNumber % 5 === 0 && $commitIndex === 1;
                        $commitCategory = $isBreaking ? CommitCategory::Breaking : $category;
                        $heading = $commitCategory->heading();
                        $commitHash = sha1($project->id.'|'.$version.'|'.$commitIndex);
                        $authoredAt = (clone $generatedAt)->subHours($commitCount - $commitIndex + 1);
                        $message = $type.'('.$scope.')'.($isBreaking ? '!' : '').': '.$subject;

                        if ($isBreaking) {
                            $message .= "\n\nBREAKING CHANGE: ".$faker->sentence(8);
                        }

                        Commit::query()->updateOrCreate(
                            [
                                'project_id' => $project->id,
                                'commit_hash' => $commitHash,
                            ],
                            [
                                'release_id' => $release->id,
                                'user_id' => $user->id,
                                'repository_full_name' => $project->github_repo,
                                'author' => $faker->name(),
                                'message' => $message,
                                'type' => $type,
                                'scope' => $scope,
                                'category' => $commitCategory->value,
                                'subject' => $subject,
                                'body' => $isBreaking ? 'BREAKING CHANGE: '.$faker->sentence(8) : null,
                                'is_breaking' => $isBreaking,
                                'source' => 'conventional',
                                'authored_at' => $authoredAt,
                                'committed_at' => $authoredAt,
                            ],
                        );

                        ProcessedCommit::query()->updateOrCreate(
                            [
                                'project_id' => $project->id,
                                'commit_hash' => $commitHash,
                            ],
                            [
                                'user_id' => $user->id,
                                'release_id' => $release->id,
                                'repository_full_name' => $project->github_repo,
                                'processed_at' => $authoredAt,
                            ],
                        );

                        $entriesByHeading[$heading][] = $subject;

                        $payloadCommits[] = [
                            'id' => $commitHash,
                            'message' => $message,
                            'author' => ['name' => $faker->name()],
                            'timestamp' => $authoredAt->toIso8601String(),
                        ];
                    }

                    $this->createChangelogEntries($release, $project, $user, $entriesByHeading);

                    $this->createWebhookDelivery($release, $project, $user, $payloadCommits, $userIndex, $projectNumber, $releaseNumber);
                }
            }
        }
    }

    /**
     * Persist ordered changelog entries and markdown content.
     *
     * @param  array<string, list<string>>  $entriesByHeading
     */
    private function createChangelogEntries(Release $release, Project $project, User $user, array $entriesByHeading): void
    {
        $position = 1;

        foreach ($entriesByHeading as $heading => $descriptions) {
            foreach ($descriptions as $description) {
                Changelog::query()->create([
                    'release_id' => $release->id,
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'category' => $heading,
                    'description' => Str::ucfirst($description),
                    'details' => null,
                    'position' => $position,
                ]);

                $position++;
            }
        }

        $markdownSections = collect($entriesByHeading)
            ->map(static function (array $descriptions, string $heading): string {
                $items = collect($descriptions)
                    ->map(static fn (string $description): string => '- '.Str::ucfirst($description))
                    ->implode("\n");

                return '## '.$heading."\n".$items;
            })
            ->values()
            ->implode("\n\n");

        $release->update([
            'markdown_content' => "# Changelog {$release->version}\n\nRepository: {$project->github_repo}\n\n{$markdownSections}\n",
        ]);
    }

    /**
     * Store a synthetic webhook delivery for each seeded release.
     *
     * @param  list<array<string, mixed>>  $payloadCommits
     */
    private function createWebhookDelivery(
        Release $release,
        Project $project,
        User $user,
        array $payloadCommits,
        int $userIndex,
        int $projectNumber,
        int $releaseNumber,
    ): void {
        WebhookDelivery::query()->updateOrCreate(
            [
                'provider' => 'github',
                'delivery_id' => sprintf('seed-u%s-p%s-r%s', $user->id, $projectNumber, $releaseNumber),
            ],
            [
                'event' => 'push',
                'project_id' => $project->id,
                'user_id' => $user->id,
                'repository_full_name' => $project->github_repo,
                'ref' => 'refs/heads/'.$project->default_branch,
                'signature_valid' => true,
                'status' => 'processed',
                'payload' => [
                    'ref' => 'refs/heads/'.$project->default_branch,
                    'repository' => ['full_name' => $project->github_repo],
                    'commits' => $payloadCommits,
                    'sender' => ['login' => Str::slug($user->name)],
                ],
                'release_id' => $release->id,
                'processed_at' => now()->subDays($userIndex + $projectNumber + $releaseNumber),
            ],
        );
    }
}
