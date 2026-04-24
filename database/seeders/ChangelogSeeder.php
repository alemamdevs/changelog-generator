<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\ProcessedCommit;
use App\Models\Project;
use App\Models\Release;
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
        $faker->seed(24042026);

        $projects = [
            ['name' => 'Changelog Generator', 'repository' => 'alemamdevs/changelog-generator', 'branch' => 'main'],
            ['name' => 'Billing API', 'repository' => 'alemamdevs/billing-api', 'branch' => 'main'],
            ['name' => 'Notifications Service', 'repository' => 'alemamdevs/notifications-service', 'branch' => 'develop'],
            ['name' => 'Admin Dashboard', 'repository' => 'alemamdevs/admin-dashboard', 'branch' => 'main'],
        ];

        $typeMap = [
            'feat' => ['category' => 'feature', 'heading' => 'Features'],
            'fix' => ['category' => 'fix', 'heading' => 'Fixes'],
            'refactor' => ['category' => 'refactor', 'heading' => 'Refactors'],
            'docs' => ['category' => 'docs', 'heading' => 'Documentation'],
            'chore' => ['category' => 'chore', 'heading' => 'Chores'],
            'perf' => ['category' => 'feature', 'heading' => 'Improvements'],
        ];

        foreach ($projects as $projectIndex => $projectData) {
            $project = Project::query()->updateOrCreate(
                ['repository_full_name' => $projectData['repository']],
                [
                    'name' => $projectData['name'],
                    'default_branch' => $projectData['branch'],
                    'is_active' => true,
                ],
            );

            $major = 1;
            $minor = 0;
            $patch = 0;

            for ($releaseNumber = 1; $releaseNumber <= 6; $releaseNumber++) {
                if ($releaseNumber === 5) {
                    $major++;
                    $minor = 0;
                    $patch = 0;
                } elseif ($releaseNumber % 3 === 0) {
                    $minor++;
                    $patch = 0;
                } else {
                    $patch++;
                }

                $version = sprintf('v%d.%d.%d', $major, $minor, $patch);
                $generatedAt = now()->subDays((7 - $releaseNumber) * 5 + $projectIndex);

                $release = Release::query()->updateOrCreate(
                    [
                        'repository_full_name' => $project->repository_full_name,
                        'version' => $version,
                    ],
                    [
                        'major' => $major,
                        'minor' => $minor,
                        'patch' => $patch,
                        'tag_name' => $version,
                        'branch' => $project->default_branch,
                        'generated_at' => $generatedAt,
                        'markdown_path' => sprintf(
                            'changelogs/%s/CHANGELOG-%s.md',
                            str_replace('/', '-', $project->repository_full_name),
                            $version,
                        ),
                    ],
                );

                $release->commits()->delete();
                $release->changelogs()->delete();

                $commitCount = 6 + (($releaseNumber + $projectIndex) % 4);
                $entriesByHeading = [];
                $payloadCommits = [];

                for ($commitIndex = 1; $commitIndex <= $commitCount; $commitIndex++) {
                    $types = array_keys($typeMap);
                    $type = $types[($commitIndex + $releaseNumber + $projectIndex) % count($types)];
                    $mappedType = $typeMap[$type];
                    $scope = ['api', 'ui', 'queue', 'security', 'release', 'infra'][($commitIndex + $projectIndex) % 6];
                    $subject = Str::of($faker->sentence(5))->lower()->rtrim('.')->value();
                    $isBreaking = $releaseNumber === 5 && $commitIndex === 1;
                    $category = $isBreaking ? 'breaking' : $mappedType['category'];
                    $heading = $isBreaking ? 'Breaking Changes' : $mappedType['heading'];
                    $commitHash = sha1($project->repository_full_name.'|'.$version.'|'.$commitIndex);
                    $authoredAt = (clone $generatedAt)->subHours($commitCount - $commitIndex + 1);
                    $message = $type.'('.$scope.')'.($isBreaking ? '!' : '').': '.$subject;

                    if ($isBreaking) {
                        $message .= "\n\nBREAKING CHANGE: ".$faker->sentence(8);
                    }

                    Commit::query()->create([
                        'release_id' => $release->id,
                        'repository_full_name' => $project->repository_full_name,
                        'commit_hash' => $commitHash,
                        'author' => $faker->name(),
                        'message' => $message,
                        'type' => $type,
                        'scope' => $scope,
                        'category' => $category,
                        'subject' => $subject,
                        'body' => $isBreaking ? 'BREAKING CHANGE: '.$faker->sentence(8) : null,
                        'is_breaking' => $isBreaking,
                        'source' => 'conventional',
                        'authored_at' => $authoredAt,
                        'committed_at' => $authoredAt,
                    ]);

                    ProcessedCommit::query()->updateOrCreate(
                        [
                            'repository_full_name' => $project->repository_full_name,
                            'commit_hash' => $commitHash,
                        ],
                        [
                            'release_id' => $release->id,
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

                $position = 1;
                foreach ($entriesByHeading as $heading => $descriptions) {
                    foreach ($descriptions as $description) {
                        Changelog::query()->create([
                            'release_id' => $release->id,
                            'category' => $heading,
                            'description' => Str::ucfirst($description),
                            'details' => null,
                            'position' => $position,
                        ]);

                        $position++;
                    }
                }

                $markdownSections = [];
                foreach ($entriesByHeading as $heading => $descriptions) {
                    $items = collect($descriptions)
                        ->map(static fn (string $description): string => '- '.Str::ucfirst($description))
                        ->implode("\n");

                    $markdownSections[] = '## '.$heading."\n".$items;
                }

                $release->update([
                    'markdown_content' => "# Changelog {$version}\n\nRepository: {$project->repository_full_name}\n\n".implode("\n\n", $markdownSections)."\n",
                ]);

                WebhookDelivery::query()->updateOrCreate(
                    [
                        'provider' => 'github',
                        'delivery_id' => substr('seed-'.$projectIndex.'-'.strtolower(str_replace('.', '-', $version)), 0, 128),
                    ],
                    [
                        'event' => 'push',
                        'repository_full_name' => $project->repository_full_name,
                        'ref' => 'refs/heads/'.$project->default_branch,
                        'signature_valid' => true,
                        'status' => 'processed',
                        'payload' => [
                            'ref' => 'refs/heads/'.$project->default_branch,
                            'repository' => ['full_name' => $project->repository_full_name],
                            'commits' => $payloadCommits,
                        ],
                        'release_id' => $release->id,
                        'processed_at' => $generatedAt,
                    ],
                );
            }
        }
    }
}
