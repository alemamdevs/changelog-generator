<?php

declare(strict_types=1);

namespace App\Repositories;

interface ProcessedCommitRepositoryInterface
{
    public function isProcessed(string $repositoryFullName, string $commitHash): bool;

    public function markProcessed(string $repositoryFullName, string $commitHash, ?int $releaseId = null): void;
}
