@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">Webhook Configuration</h1>
        <p class="mt-1 text-sm text-slate-400">Connect GitHub and GitLab push events using one common endpoint.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-medium text-white">Common Endpoint</h2>
            <p class="mt-2 text-sm text-slate-400">Use this endpoint for both GitHub and GitLab webhooks.</p>

            <div class="mt-4 rounded-lg border border-slate-700 bg-slate-950 p-3 text-xs text-emerald-300">
                {{ $commonEndpoint }}
            </div>

            <div class="mt-4 space-y-2 text-sm text-slate-300">
                <p>Queue connection: <span class="font-medium text-white">{{ $queueConnection }}</span></p>
                <p>Queue name: <span class="font-medium text-white">{{ $queueName }}</span></p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-medium text-white">Provider Secrets</h2>
            <p class="mt-2 text-sm text-slate-400">Set these values in your <code>.env</code> file.</p>

            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                    <span class="text-slate-200">GITHUB_WEBHOOK_SECRET</span>
                    <span class="rounded-md px-2 py-1 text-xs {{ $githubSecretConfigured ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                        {{ $githubSecretConfigured ? 'Configured' : 'Missing' }}
                    </span>
                </li>
                <li class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950 px-3 py-2">
                    <span class="text-slate-200">GITLAB_WEBHOOK_SECRET</span>
                    <span class="rounded-md px-2 py-1 text-xs {{ $gitlabSecretConfigured ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                        {{ $gitlabSecretConfigured ? 'Configured' : 'Missing' }}
                    </span>
                </li>
            </ul>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-medium text-white">GitHub Setup</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-300">
                <li>Repository Settings → Webhooks → Add webhook</li>
                <li>Payload URL: <code>{{ $commonEndpoint }}</code></li>
                <li>Content type: <code>application/json</code></li>
                <li>Secret: <code>GITHUB_WEBHOOK_SECRET</code></li>
                <li>Events: <code>Just the push event</code></li>
            </ol>
            <p class="mt-3 text-xs text-slate-500">Legacy endpoint still supported: <code>{{ $githubEndpoint }}</code></p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-medium text-white">GitLab Setup</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-300">
                <li>Project Settings → Webhooks</li>
                <li>URL: <code>{{ $commonEndpoint }}</code></li>
                <li>Secret token: <code>GITLAB_WEBHOOK_SECRET</code></li>
                <li>Trigger: <code>Push events</code></li>
            </ol>
            <p class="mt-3 text-xs text-slate-500">Legacy endpoint still supported: <code>{{ $gitlabEndpoint }}</code></p>
        </section>
    </div>
@endsection
