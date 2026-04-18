<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GitWebhookRequest;
use App\Jobs\ProcessGitHubPushWebhookJob;
use App\Models\WebhookDelivery;
use App\Services\Security\GitHubWebhookSignatureService;
use App\Services\Security\GitLabWebhookTokenService;
use Illuminate\Http\JsonResponse;

/**
 * Controller that receives GitHub / GitLab push webhooks and dispatches
 * background processing for changelog generation.
 */
final class GitWebhookController extends Controller
{
    public function __construct(
        private GitHubWebhookSignatureService $gitHubSignatureService,
        private GitLabWebhookTokenService $gitLabTokenService,
    ) {}

    /**
     * Handle incoming webhook POST requests.
     */
    public function handle(GitWebhookRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $provider = $this->detectProvider($request);

        if ($provider === null) {
            return response()->json(['message' => 'Unsupported webhook provider.'], 422);
        }

        if (! $this->isPushEvent($request, $provider)) {
            return response()->json(['message' => 'Only push events are supported.'], 422);
        }

        if (! $this->isValidSignature($request, $provider)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $repositoryFullName = (string) data_get(
            $payload,
            'repository.full_name',
            (string) data_get($payload, 'project.path_with_namespace', ''),
        );

        if ($repositoryFullName === '') {
            return response()->json(['message' => 'Repository identifier not found in payload.'], 422);
        }

        $delivery = WebhookDelivery::query()->create([
            'provider' => $provider,
            'event' => $this->normalizedEventName($provider),
            'delivery_id' => $this->deliveryId($request, $provider),
            'repository_full_name' => $repositoryFullName,
            'ref' => (string) data_get($payload, 'ref'),
            'signature_valid' => true,
            'status' => 'queued',
            'payload' => $payload,
        ]);

        ProcessGitHubPushWebhookJob::dispatch($delivery->id)
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

    private function isValidSignature(GitWebhookRequest $request, string $provider): bool
    {
        if ($provider === 'github') {
            return $this->gitHubSignatureService->isValid(
                $request->getContent(),
                $request->header('X-Hub-Signature-256'),
            );
        }

        return $this->gitLabTokenService->isValid($request->header('X-Gitlab-Token'));
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
}
