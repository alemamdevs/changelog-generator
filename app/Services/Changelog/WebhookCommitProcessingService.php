<?php

declare(strict_types=1);

namespace App\Services\Changelog;

use App\Models\Release;
use App\Models\WebhookDelivery;
use App\Repositories\ChangelogEntryRepositoryInterface;
use App\Repositories\ChangelogRunRepositoryInterface;
use App\Repositories\CommitRepositoryInterface;
use App\Repositories\ProcessedCommitRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class WebhookCommitProcessingService
{
    public function __construct(
        private ProcessedCommitRepositoryInterface $processedCommitRepository,
        private ChangelogRunRepositoryInterface $runRepository,
        private CommitRepositoryInterface $commitRepository,
        private ChangelogEntryRepositoryInterface $changelogEntryRepository,
        private CommitCategorizerService $categorizer,
        private SemverVersionService $semver,
        private MarkdownChangelogRendererService $markdown,
    ) {}

    public function processDelivery(WebhookDelivery $delivery): ?Release
    {
        $payload = (array) $delivery->payload;
        $repositoryFullName = (string) data_get($payload, 'repository.full_name', $delivery->repository_full_name);
        $ref = (string) data_get($payload, 'ref', $delivery->ref);
        $branch = (string) collect(explode('/', $ref))->last();

        $commits = collect((array) data_get($payload, 'commits', []))
            ->map(function (mixed $commit) {
                $hash = (string) data_get($commit, 'id', data_get($commit, 'hash', ''));

                return [
                    'hash' => $hash,
                    'message' => (string) data_get($commit, 'message', ''),
                    'author' => (string) data_get($commit, 'author.name', data_get($commit, 'author.username', 'unknown')),
                    'timestamp' => data_get($commit, 'timestamp'),
                ];
            })
            ->filter(fn (array $commit): bool => $commit['hash'] !== '' && $commit['message'] !== '')
            ->values();

        if ($commits->isEmpty()) {
            $delivery->update([
                'status' => 'skipped',
                'processed_at' => now(),
                'error_message' => 'No commits found in payload.',
            ]);

            return null;
        }

        $newCommits = $commits
            ->reject(fn (array $commit): bool => $this->processedCommitRepository->isProcessed($repositoryFullName, $commit['hash']))
            ->values();

        if ($newCommits->isEmpty()) {
            $delivery->update([
                'status' => 'duplicate',
                'processed_at' => now(),
            ]);

            return null;
        }

        $categorized = $newCommits->map(function (array $commit): array {
            return array_merge($commit, $this->categorizer->categorize($commit['message']));
        })->values();

        $latestRelease = Release::query()
            ->where('repository_full_name', $repositoryFullName)
            ->latest('generated_at')
            ->first();

        $versionData = $this->semver->nextVersion($latestRelease, $categorized->all());

        return DB::transaction(function () use ($categorized, $delivery, $versionData, $repositoryFullName, $branch): Release {
            $release = $this->runRepository->createRun([
                'version' => $versionData['version'],
                'major' => $versionData['major'],
                'minor' => $versionData['minor'],
                'patch' => $versionData['patch'],
                'tag_name' => $versionData['tag_name'],
                'branch' => $branch,
                'repository_full_name' => $repositoryFullName,
                'generated_at' => now(),
            ]);

            $position = 0;
            $groupedCommits = [];

            foreach ($categorized as $commit) {
                $this->commitRepository->createCommit([
                    'release_id' => $release->id,
                    'repository_full_name' => $repositoryFullName,
                    'commit_hash' => $commit['hash'],
                    'author' => $commit['author'],
                    'message' => $commit['message'],
                    'type' => $commit['type'],
                    'scope' => $commit['scope'],
                    'category' => $commit['category']->value,
                    'is_breaking' => $commit['is_breaking'],
                    'subject' => $commit['subject'],
                    'body' => $commit['body'],
                    'source' => $commit['source'],
                    'authored_at' => $commit['timestamp'],
                    'committed_at' => $commit['timestamp'],
                ]);

                $this->changelogEntryRepository->createEntry([
                    'release_id' => $release->id,
                    'category' => $commit['category']->heading(),
                    'description' => $commit['subject'],
                    'details' => $commit['body'],
                    'position' => $position++,
                ]);

                $groupedCommits[$commit['category']->value][] = [
                    'subject' => $commit['subject'],
                    'hash' => $commit['hash'],
                    'scope' => $commit['scope'],
                    'category' => $commit['category'],
                ];

                $this->processedCommitRepository->markProcessed($repositoryFullName, $commit['hash'], $release->id);
            }

            $rendered = $this->markdown->renderAndStore($release, $groupedCommits);

            $release->update([
                'markdown_path' => $rendered['path'],
                'markdown_content' => $rendered['content'],
            ]);

            $delivery->update([
                'status' => 'processed',
                'release_id' => $release->id,
                'processed_at' => now(),
            ]);

            return $release->fresh();
        });
    }
}
