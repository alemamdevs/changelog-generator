<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProcessedCommit;

final class EloquentProcessedCommitRepository implements ProcessedCommitRepositoryInterface
{
    public function isProcessed(int $projectId, string $commitHash): bool
    {
        $query = ProcessedCommit::query()
            ->where('project_id', $projectId)
            ->where('commit_hash', $commitHash);

        return $query->exists();
    }

    public function markProcessed(int $projectId, int $userId, string $repositoryFullName, string $commitHash, ?int $releaseId = null): void
    {
        ProcessedCommit::query()->updateOrCreate(
            [
                'project_id' => $projectId,
                'user_id' => $userId,
                'repository_full_name' => $repositoryFullName,
                'commit_hash' => $commitHash,
            ],
            [
                'release_id' => $releaseId,
                'processed_at' => now(),
            ],
        );
    }
}
