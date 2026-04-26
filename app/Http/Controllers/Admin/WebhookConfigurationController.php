<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateWebhookSecretsRequest;
use App\Services\Security\WebhookSecretEnvironmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WebhookConfigurationController extends Controller
{
    public function index(): View
    {
        return view('admin.webhooks.configuration', [
            'commonEndpoint' => route('api.webhooks.push'),
            'githubEndpoint' => route('api.github.webhooks.push'),
            'gitlabEndpoint' => route('api.gitlab.webhooks.push'),
            'githubSecret' => (string) config('services.github.webhook_secret', ''),
            'gitlabSecret' => (string) config('services.gitlab.webhook_secret', ''),
            'githubSecretConfigured' => (string) config('services.github.webhook_secret', '') !== '',
            'gitlabSecretConfigured' => (string) config('services.gitlab.webhook_secret', '') !== '',
            'queueConnection' => (string) config('changelog.queue.connection', 'redis'),
            'queueName' => (string) config('changelog.queue.name', 'changelog'),
        ]);
    }

    public function generateMissingSecrets(
        GenerateWebhookSecretsRequest $request,
        WebhookSecretEnvironmentService $webhookSecretEnvironmentService,
    ): RedirectResponse|JsonResponse {
        $generatedSecrets = $webhookSecretEnvironmentService->ensureMissingSecrets($request->provider());
        $githubSecret = (string) config('services.github.webhook_secret', '');
        $gitlabSecret = (string) config('services.gitlab.webhook_secret', '');

        if ($generatedSecrets === []) {
            $message = 'Webhook secret is already configured for the selected provider.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'generated' => [],
                    'message' => $message,
                    'secrets' => [
                        'github' => $githubSecret,
                        'gitlab' => $gitlabSecret,
                    ],
                    'configured' => [
                        'github' => $githubSecret !== '',
                        'gitlab' => $gitlabSecret !== '',
                    ],
                ]);
            }

            return redirect()
                ->route('admin.webhooks.configuration')
                ->with('status', $message);
        }

        if (array_key_exists('GITHUB_WEBHOOK_SECRET', $generatedSecrets)) {
            config()->set('services.github.webhook_secret', $generatedSecrets['GITHUB_WEBHOOK_SECRET']);
            $githubSecret = (string) $generatedSecrets['GITHUB_WEBHOOK_SECRET'];
        }

        if (array_key_exists('GITLAB_WEBHOOK_SECRET', $generatedSecrets)) {
            config()->set('services.gitlab.webhook_secret', $generatedSecrets['GITLAB_WEBHOOK_SECRET']);
            $gitlabSecret = (string) $generatedSecrets['GITLAB_WEBHOOK_SECRET'];
        }

        $generatedKeys = implode(', ', array_keys($generatedSecrets));
        $message = 'Generated and saved: '.$generatedKeys;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'generated' => array_keys($generatedSecrets),
                'message' => $message,
                'secrets' => [
                    'github' => $githubSecret,
                    'gitlab' => $gitlabSecret,
                ],
                'configured' => [
                    'github' => $githubSecret !== '',
                    'gitlab' => $gitlabSecret !== '',
                ],
            ]);
        }

        return redirect()
            ->route('admin.webhooks.configuration')
            ->with('status', $message);
    }
}
