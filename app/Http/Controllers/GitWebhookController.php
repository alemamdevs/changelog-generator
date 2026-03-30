<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GitWebhookRequest;
use App\Jobs\GenerateChangelogJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller that receives Git hosting webhooks (GitHub/GitLab) and
 * dispatches background work to generate changelogs.
 */
final class GitWebhookController extends Controller
{
    /**
     * Handle incoming webhook POST requests.
     */
    public function handle(GitWebhookRequest $request): JsonResponse
    {
        $payload = $request->all();

        try {
            if (! $this->verifySignature($request)) {
                Log::warning('Git webhook signature verification failed', [
                    'headers' => $request->headers->all(),
                ]);

                return response()->json(['message' => 'Invalid signature'], 403);
            }

            $commits = $payload['commits'] ?? [];

            $meta = [
                'repository' => $payload['repository'] ?? null,
                'ref' => $payload['ref'] ?? null,
                'event' => $request->header('X-GitHub-Event') ?? $request->header('X-Gitlab-Event'),
            ];

            GenerateChangelogJob::dispatch($commits, $meta);

            return response()->json(['status' => 'queued'], 202);
        } catch (\Throwable $e) {
            Log::error('Error handling git webhook', ['exception' => $e]);

            return response()->json(['message' => 'Server error'], 500);
        }
    }

    /**
     * Verify webhook signature for GitHub (X-Hub-Signature-256) or GitLab (X-Gitlab-Token).
     */
    private function verifySignature(GitWebhookRequest $request): bool
    {
        $content = $request->getContent();

        // GitHub signature
        $githubSignature = $request->header('X-Hub-Signature-256');

        if ($githubSignature !== null) {
            $secret = config('services.github.webhook_secret');

            if (empty($secret)) {
                // If no secret configured, fail safe.
                return false;
            }

            $computed = 'sha256=' . hash_hmac('sha256', $content, (string) $secret);

            return hash_equals($computed, $githubSignature);
        }

        // GitLab token
        $gitlabToken = $request->header('X-Gitlab-Token');

        if ($gitlabToken !== null) {
            $secret = config('services.gitlab.webhook_secret');

            if (empty($secret)) {
                return false;
            }

            return hash_equals((string) $secret, $gitlabToken);
        }

        // Unknown provider or no signature header present
        return false;
    }
}
