<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProcessedCommit;

final class EloquentProcessedCommitRepository implements ProcessedCommitRepositoryInterface
{
    public function isProcessed(string $repositoryFullName, string $commitHash): bool
    {
        return ProcessedCommit::query()
            ->where('repository_full_name', $repositoryFullName)
            ->where('commit_hash', $commitHash)
            ->exists();
    }

    public function markProcessed(string $repositoryFullName, string $commitHash, ?int $releaseId = null): void
    {
        ProcessedCommit::query()->updateOrCreate(
            [
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
