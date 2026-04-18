<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\ProcessedCommit;
use App\Models\Release;
use App\Models\WebhookDelivery;
use Illuminate\Database\Seeder;

class ChangelogSeeder extends Seeder
{
    /**
     * Seed meaningful preview data for dashboard pages.
     */
    public function run(): void
    {
        $repository = 'alemamdevs/changelog-generator';

        $releases = [
            [
                'version' => 'v1.0.0',
                'major' => 1,
                'minor' => 0,
                'patch' => 0,
                'tag_name' => 'v1.0.0',
                'branch' => 'main',
                'repository_full_name' => $repository,
                'generated_at' => now()->subDays(18),
                'markdown_path' => 'changelogs/alemamdevs-changelog-generator/CHANGELOG-v1.0.0.md',
                'markdown_content' => "# Changelog v1.0.0\n\n## Features\n- Initial changelog generation pipeline\n\n## Fixes\n- Fixed webhook payload parsing edge case\n",
                'commits' => [
                    [
                        'commit_hash' => 'a12f34b56c78d90e12f34b56c78d90e12f34a001',
                        'author' => 'Ahmed Emam',
                        'message' => 'feat(generator): bootstrap changelog generation pipeline',
                        'type' => 'feat',
                        'scope' => 'generator',
                        'category' => 'feature',
                        'subject' => 'bootstrap changelog generation pipeline',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(20),
                    ],
                    [
                        'commit_hash' => 'b45d67e89f01a23b45d67e89f01a23b45d67e002',
                        'author' => 'Sarah Malik',
                        'message' => 'fix(webhook): normalize commit author payload',
                        'type' => 'fix',
                        'scope' => 'webhook',
                        'category' => 'fix',
                        'subject' => 'normalize commit author payload',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(19),
                    ],
                ],
                'entries' => [
                    ['category' => 'Features', 'description' => 'Bootstrap changelog generation pipeline'],
                    ['category' => 'Fixes', 'description' => 'Normalize commit author payload from webhook'],
                ],
            ],
            [
                'version' => 'v1.1.0',
                'major' => 1,
                'minor' => 1,
                'patch' => 0,
                'tag_name' => 'v1.1.0',
                'branch' => 'main',
                'repository_full_name' => $repository,
                'generated_at' => now()->subDays(9),
                'markdown_path' => 'changelogs/alemamdevs-changelog-generator/CHANGELOG-v1.1.0.md',
                'markdown_content' => "# Changelog v1.1.0\n\n## Features\n- Added release dashboard\n\n## Refactors\n- Refactored commit categorizer service\n\n## Documentation\n- Added webhook setup guide\n",
                'commits' => [
                    [
                        'commit_hash' => 'c78a90b12c34d56e78a90b12c34d56e78a90b003',
                        'author' => 'Mina Youssef',
                        'message' => 'feat(ui): add releases and webhook monitor views',
                        'type' => 'feat',
                        'scope' => 'ui',
                        'category' => 'feature',
                        'subject' => 'add releases and webhook monitor views',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(11),
                    ],
                    [
                        'commit_hash' => 'd90e12f34a56b78d90e12f34a56b78d90e12f004',
                        'author' => 'Ahmed Emam',
                        'message' => 'refactor(changelog): split categorizer and renderer services',
                        'type' => 'refactor',
                        'scope' => 'changelog',
                        'category' => 'refactor',
                        'subject' => 'split categorizer and renderer services',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(10),
                    ],
                    [
                        'commit_hash' => 'e12f34a56b78c90e12f34a56b78c90e12f34a005',
                        'author' => 'Sarah Malik',
                        'message' => 'docs(readme): add GitHub webhook configuration section',
                        'type' => 'docs',
                        'scope' => 'readme',
                        'category' => 'docs',
                        'subject' => 'add GitHub webhook configuration section',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(9),
                    ],
                ],
                'entries' => [
                    ['category' => 'Features', 'description' => 'Added releases and webhook monitoring views'],
                    ['category' => 'Refactors', 'description' => 'Split categorizer and renderer services'],
                    ['category' => 'Documentation', 'description' => 'Added GitHub webhook configuration guide'],
                ],
            ],
            [
                'version' => 'v2.0.0',
                'major' => 2,
                'minor' => 0,
                'patch' => 0,
                'tag_name' => 'v2.0.0',
                'branch' => 'main',
                'repository_full_name' => $repository,
                'generated_at' => now()->subDays(2),
                'markdown_path' => 'changelogs/alemamdevs-changelog-generator/CHANGELOG-v2.0.0.md',
                'markdown_content' => "# Changelog v2.0.0\n\n## Breaking Changes\n- Migrated changelog storage schema\n\n## Features\n- Queue-driven webhook processing\n\n## Chores\n- Production queue tuning\n",
                'commits' => [
                    [
                        'commit_hash' => 'f34a56b78c90d12f34a56b78c90d12f34a56b006',
                        'author' => 'Mina Youssef',
                        'message' => "feat(storage)!: migrate changelog schema to normalized tables\n\nBREAKING CHANGE: release structure now includes semver metadata",
                        'type' => 'feat',
                        'scope' => 'storage',
                        'category' => 'breaking',
                        'subject' => 'migrate changelog schema to normalized tables',
                        'body' => 'BREAKING CHANGE: release structure now includes semver metadata',
                        'is_breaking' => true,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(3),
                    ],
                    [
                        'commit_hash' => 'a56b78c90d12e34a56b78c90d12e34a56b78c007',
                        'author' => 'Ahmed Emam',
                        'message' => 'feat(queue): process GitHub push payloads asynchronously',
                        'type' => 'feat',
                        'scope' => 'queue',
                        'category' => 'feature',
                        'subject' => 'process GitHub push payloads asynchronously',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(2),
                    ],
                    [
                        'commit_hash' => 'b78c90d12e34f56b78c90d12e34f56b78c90d008',
                        'author' => 'Sarah Malik',
                        'message' => 'chore(queue): tune retries and backoff for production load',
                        'type' => 'chore',
                        'scope' => 'queue',
                        'category' => 'chore',
                        'subject' => 'tune retries and backoff for production load',
                        'is_breaking' => false,
                        'source' => 'conventional',
                        'committed_at' => now()->subDays(1),
                    ],
                ],
                'entries' => [
                    ['category' => 'Breaking Changes', 'description' => 'Migrated changelog schema to normalized storage'],
                    ['category' => 'Features', 'description' => 'Queue-driven GitHub webhook processing'],
                    ['category' => 'Chores', 'description' => 'Improved queue retry and backoff tuning'],
                ],
            ],
        ];

        foreach ($releases as $releaseData) {
            $release = Release::query()->create([
                'version' => $releaseData['version'],
                'major' => $releaseData['major'],
                'minor' => $releaseData['minor'],
                'patch' => $releaseData['patch'],
                'tag_name' => $releaseData['tag_name'],
                'branch' => $releaseData['branch'],
                'repository_full_name' => $releaseData['repository_full_name'],
                'generated_at' => $releaseData['generated_at'],
                'markdown_path' => $releaseData['markdown_path'],
                'markdown_content' => $releaseData['markdown_content'],
            ]);

            foreach ($releaseData['commits'] as $commitData) {
                $commit = Commit::query()->create([
                    'release_id' => $release->id,
                    'repository_full_name' => $releaseData['repository_full_name'],
                    'commit_hash' => $commitData['commit_hash'],
                    'author' => $commitData['author'],
                    'message' => $commitData['message'],
                    'type' => $commitData['type'],
                    'scope' => $commitData['scope'],
                    'category' => $commitData['category'],
                    'subject' => $commitData['subject'],
                    'body' => $commitData['body'] ?? null,
                    'is_breaking' => $commitData['is_breaking'],
                    'source' => $commitData['source'],
                    'authored_at' => $commitData['committed_at'],
                    'committed_at' => $commitData['committed_at'],
                ]);

                ProcessedCommit::query()->create([
                    'repository_full_name' => $releaseData['repository_full_name'],
                    'commit_hash' => $commit->commit_hash,
                    'release_id' => $release->id,
                    'processed_at' => $commit->committed_at,
                ]);
            }

            foreach ($releaseData['entries'] as $position => $entryData) {
                Changelog::query()->create([
                    'release_id' => $release->id,
                    'category' => $entryData['category'],
                    'description' => $entryData['description'],
                    'details' => null,
                    'position' => $position + 1,
                ]);
            }

            WebhookDelivery::query()->create([
                'provider' => 'github',
                'event' => 'push',
                'delivery_id' => 'seed-'.strtolower(str_replace('.', '-', $release->version)),
                'repository_full_name' => $releaseData['repository_full_name'],
                'ref' => 'refs/heads/'.$releaseData['branch'],
                'signature_valid' => true,
                'status' => 'processed',
                'payload' => [
                    'ref' => 'refs/heads/'.$releaseData['branch'],
                    'repository' => ['full_name' => $releaseData['repository_full_name']],
                    'commits' => array_map(fn (array $item): array => [
                        'id' => $item['commit_hash'],
                        'message' => $item['message'],
                        'author' => ['name' => $item['author']],
                        'timestamp' => (string) $item['committed_at'],
                    ], $releaseData['commits']),
                ],
                'release_id' => $release->id,
                'processed_at' => $releaseData['generated_at'],
            ]);
        }
    }
}
