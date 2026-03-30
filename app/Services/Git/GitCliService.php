<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Services\Git\IGitService;
use Illuminate\Support\Facades\Log;

/**
 * A small Git CLI based implementation of IGitService. It attempts to use
 * commits supplied by webhooks first, and falls back to cloning/fetching a
 * shallow repository to compute commits between two refs.
 */
final class GitCliService implements IGitService
{
    /**
     * {@inheritDoc}
     */
    public function fetchCommitsFromWebhookPayload(array $meta): array
    {
        // If the webhook already included commits (e.g., GitHub/GitLab payload)
        if (! empty($meta['commits']) && is_array($meta['commits'])) {
            return $meta['commits'];
        }

        $repo = $meta['repository'] ?? null;

        $repoUrl = $repo['clone_url'] ?? $repo['git_ssh_url'] ?? $repo['url'] ?? null;
        $from = $meta['before'] ?? null;
        $to = $meta['after'] ?? null;

        if (! $repoUrl || ! $from || ! $to) {
            Log::warning('GitCliService: insufficient metadata to fetch commits', ['meta' => $meta]);
            return [];
        }

        $dest = storage_path('app/private/repos/' . md5($repoUrl . $to));

        try {
            if (! is_dir($dest)) {
                // shallow clone
                $cloneCmd = ['git', 'clone', '--quiet', '--no-tags', '--depth', '50', $repoUrl, $dest];
                $p = new \Symfony\Component\Process\Process($cloneCmd);
                $p->setTimeout(120)->run();

                if (! $p->isSuccessful()) {
                    Log::error('GitCliService: git clone failed', ['url' => $repoUrl, 'output' => $p->getErrorOutput() ?: $p->getOutput()]);
                    return [];
                }
            } else {
                $fetch = new \Symfony\Component\Process\Process(['git', '-C', $dest, 'fetch', '--quiet', 'origin']);
                $fetch->setTimeout(60)->run();
            }

            // Get commits between the two SHAs (from..to)
            $logCmd = ['git', '-C', $dest, 'log', '--pretty=format:%H|%an|%ai|%s', $from . '..' . $to];
            $log = new \Symfony\Component\Process\Process($logCmd);
            $log->setTimeout(60)->run();

            if (! $log->isSuccessful()) {
                Log::error('GitCliService: git log failed', ['dest' => $dest, 'error' => $log->getErrorOutput() ?: $log->getOutput()]);
                return [];
            }

            $out = trim($log->getOutput());

            if ($out === '') {
                return [];
            }

            $lines = preg_split('/\r?\n/', $out) ?: [];

            $commits = [];

            foreach ($lines as $line) {
                $parts = explode('|', $line, 4) + [null, null, null, null];
                [$hash, $author, $date, $message] = $parts;

                $commits[] = [
                    'hash' => $hash,
                    'author' => $author,
                    'timestamp' => $date,
                    'message' => $message,
                ];
            }

            return $commits;
        } catch (\Throwable $e) {
            Log::error('GitCliService exception while fetching commits', ['exception' => $e]);
            return [];
        }
    }
}
