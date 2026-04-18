<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

final class WebhookConfigurationController extends Controller
{
    public function index()
    {
        return view('admin.webhooks.configuration', [
            'commonEndpoint' => route('api.webhooks.push'),
            'githubEndpoint' => route('api.github.webhooks.push'),
            'gitlabEndpoint' => route('api.gitlab.webhooks.push'),
            'githubSecretConfigured' => (string) config('services.github.webhook_secret', '') !== '',
            'gitlabSecretConfigured' => (string) config('services.gitlab.webhook_secret', '') !== '',
            'queueConnection' => (string) config('changelog.queue.connection', 'redis'),
            'queueName' => (string) config('changelog.queue.name', 'changelog'),
        ]);
    }
}
