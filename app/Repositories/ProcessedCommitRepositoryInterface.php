<?php

declare(strict_types=1);

namespace App\Repositories;

interface ProcessedCommitRepositoryInterface
{
    public function isProcessed(int $projectId, string $commitHash): bool;

    public function markProcessed(int $projectId, int $userId, string $repositoryFullName, string $commitHash, ?int $releaseId = null): void;
}
