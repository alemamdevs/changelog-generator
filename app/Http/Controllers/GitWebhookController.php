<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GitWebhookRequest;
use App\Jobs\ProcessGitHubPushWebhookJob;
use App\Models\WebhookDelivery;
use App\Services\Security\WebhookProjectResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller that receives GitHub / GitLab push webhooks and dispatches
 * background processing for changelog generation.
 */
final class GitWebhookController extends Controller
{
    public function __construct(
        private WebhookProjectResolver $webhookProjectResolver,
    ) {}

    /**
     * Handle incoming webhook POST requests.
     */
    public function handle(GitWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $provider = $this->detectProvider($request);
        $repositoryFullName = $this->webhookProjectResolver->repositoryFullName($payload);

        if ($provider === null) {
            $this->logSuspiciousAccess('unsupported_provider', $request, $repositoryFullName);

            return response()->json(['message' => 'Unsupported webhook provider.'], 422);
        }

        if (! $this->isPushEvent($request, $provider)) {
            $this->logSuspiciousAccess('unsupported_event', $request, $repositoryFullName, ['provider' => $provider]);

            return response()->json(['message' => 'Only push events are supported.'], 422);
        }

        if ($repositoryFullName === '') {
            $this->logSuspiciousAccess('missing_repository_identifier', $request, $repositoryFullName, ['provider' => $provider]);

            return response()->json(['message' => 'Repository identifier not found in payload.'], 422);
        }

        if ($provider === 'github') {
            $candidates = $this->webhookProjectResolver->githubCandidates($repositoryFullName);

            if ($candidates->isEmpty()) {
                $this->logSuspiciousAccess('project_not_found', $request, $repositoryFullName, ['provider' => $provider]);

                return response()->json(['message' => 'Webhook project not found.'], 404);
            }

            $project = $this->webhookProjectResolver->resolveGithubProject(
                $request->getContent(),
                $request->header('X-Hub-Signature-256'),
                $candidates,
            );

            if ($project === null) {
                $this->logSuspiciousAccess('invalid_signature', $request, $repositoryFullName, ['provider' => $provider]);

                return response()->json(['message' => 'Invalid signature.'], 401);
            }
        } else {
            $token = (string) $request->header('X-Gitlab-Token', '');

            if ($token === '') {
                $this->logSuspiciousAccess('missing_gitlab_token', $request, $repositoryFullName, ['provider' => $provider]);

                return response()->json(['message' => 'Invalid signature.'], 401);
            }

            $candidates = $this->webhookProjectResolver->gitlabCandidates($token);

            if ($candidates->isEmpty()) {
                $this->logSuspiciousAccess('project_not_found', $request, $repositoryFullName, ['provider' => $provider]);

                return response()->json(['message' => 'Invalid signature.'], 401);
            }

            $project = $this->webhookProjectResolver->resolveGitLabProject($token, $repositoryFullName, $candidates);

            if ($project === null) {
                $this->logSuspiciousAccess('repository_mismatch', $request, $repositoryFullName, ['provider' => $provider]);

                return response()->json(['message' => 'Repository identifier does not match the webhook secret.'], 422);
            }
        }

        $delivery = WebhookDelivery::query()->create([
            'provider' => $provider,
            'event' => $this->normalizedEventName($provider),
            'delivery_id' => $this->deliveryId($request, $provider),
            'project_id' => $project->id,
            'user_id' => $project->user_id,
            'repository_full_name' => $repositoryFullName,
            'ref' => (string) data_get($payload, 'ref'),
            'signature_valid' => true,
            'status' => 'queued',
            'payload' => $payload,
        ]);

        ProcessGitHubPushWebhookJob::dispatch($delivery->id, $project->id, $project->user_id)
            ->onConnection((string) config('changelog.queue.connection', 'redis'))
            ->onQueue((string) config('changelog.queue.name', 'changelog'));

        return response()->json([
            'status' => 'queued',
            'delivery_id' => $delivery->id,
        ], 202);
    }

    private function detectProvider(GitWebhookRequest $request): ?string
    {
        if ($request->header('X-GitHub-Event') !== null) {
            return 'github';
        }

        if ($request->header('X-Gitlab-Event') !== null) {
            return 'gitlab';
        }

        return null;
    }

    private function isPushEvent(GitWebhookRequest $request, string $provider): bool
    {
        if ($provider === 'github') {
            return (string) $request->header('X-GitHub-Event', '') === 'push';
        }

        return (string) $request->header('X-Gitlab-Event', '') === 'Push Hook';
    }

    private function deliveryId(GitWebhookRequest $request, string $provider): string
    {
        if ($provider === 'github') {
            $headerValue = (string) $request->header('X-GitHub-Delivery', '');

            return $headerValue !== '' ? $headerValue : uniqid('github-', true);
        }

        $headerValue = (string) $request->header('X-Gitlab-Event-UUID', '');

        return $headerValue !== '' ? $headerValue : uniqid('gitlab-', true);
    }

    private function normalizedEventName(string $provider): string
    {
        return $provider === 'github' ? 'push' : 'Push Hook';
    }

    /**
     * Log suspicious webhook activity.
     */
    private function logSuspiciousAccess(string $reason, GitWebhookRequest $request, string $repositoryFullName, array $context = []): void
    {
        Log::warning('Suspicious webhook access attempt', array_merge([
            'reason' => $reason,
            'provider' => $context['provider'] ?? null,
            'repository_full_name' => $repositoryFullName,
            'delivery_id' => $this->deliveryId($request, $context['provider'] ?? 'github'),
            'ip' => $request->ip(),
            'path' => $request->path(),
        ], $context));
    }
}
