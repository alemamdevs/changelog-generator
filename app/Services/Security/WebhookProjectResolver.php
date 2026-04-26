<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class WebhookProjectResolver
{
    public function __construct(
        private GitHubWebhookSignatureService $gitHubWebhookSignatureService,
        private GitLabWebhookTokenService $gitLabWebhookTokenService,
    ) {}

    public function repositoryFullName(array $payload): string
    {
        return (string) data_get(
            $payload,
            'repository.full_name',
            (string) data_get($payload, 'project.path_with_namespace', ''),
        );
    }

    public function githubCandidates(string $repositoryFullName): Collection
    {
        return $this->rememberCandidates(
            'github',
            $repositoryFullName,
            fn () => Project::query()
                ->where('github_repo', $repositoryFullName)
                ->orderBy('id')
                ->get(),
        );
    }

    public function gitlabCandidates(string $webhookSecret): Collection
    {
        return $this->rememberCandidates(
            'gitlab',
            $webhookSecret,
            fn () => Project::query()
                ->where('webhook_secret_hash', hash('sha256', $webhookSecret))
                ->orderBy('id')
                ->get(),
        );
    }

    public function resolveGithubProject(string $rawPayload, ?string $signatureHeader, Collection $candidates): ?Project
    {
        foreach ($candidates as $project) {
            if ($this->gitHubWebhookSignatureService->isValid($rawPayload, $signatureHeader, $project->webhook_secret)) {
                return $project;
            }
        }

        return null;
    }

    public function resolveGitLabProject(?string $tokenHeader, string $repositoryFullName, Collection $candidates): ?Project
    {
        if ($tokenHeader === null || $tokenHeader === '') {
            return null;
        }

        foreach ($candidates as $project) {
            if ($project->github_repo !== $repositoryFullName) {
                continue;
            }

            if ($this->gitLabWebhookTokenService->isValid($tokenHeader, $project->webhook_secret)) {
                return $project;
            }
        }

        return null;
    }

    private function rememberCandidates(string $provider, string $lookupValue, \Closure $resolver): Collection
    {
        return Cache::remember(
            $this->cacheKey($provider, $lookupValue),
            now()->addMinutes(5),
            fn (): Collection => $resolver(),
        );
    }

    private function cacheKey(string $provider, string $lookupValue): string
    {
        return 'webhook-project-candidates:'.$provider.':'.sha1($lookupValue);
    }
}
