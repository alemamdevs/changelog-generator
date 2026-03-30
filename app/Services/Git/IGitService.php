<?php

declare(strict_types=1);

namespace App\Services\Git;

/**
 * Minimal interface for Git service used by the job.
 */
interface IGitService
{
    /**
     * Fetch commits for a webhook payload. Implementations should extract commits
     * from the given webhook metadata or clone/fetch the repository as needed.
     *
     * @param array<string,mixed> $meta
     * @return array<int,mixed>
     */
    public function fetchCommitsFromWebhookPayload(array $meta): array;
}
