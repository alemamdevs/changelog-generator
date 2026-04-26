@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">Webhook Configuration</h1>
        <p class="mt-1 text-sm text-slate-400">Connect GitHub and GitLab push events using one common endpoint.</p>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" id="serverStatusBanner">
            {{ session('status') }}
        </div>
    @endif

    <div id="dynamicStatusBanner" class="mb-6 hidden rounded-xl border px-4 py-3 text-sm transition-all duration-300"></div>

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
            <p class="mt-2 text-sm text-slate-400">Review each secret, copy it instantly, or generate a missing one without leaving this page.</p>

            <div class="mt-5 space-y-4">
                <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4" data-provider-card="github">
                    <div class="mb-2 flex items-center justify-between">
                        <label for="githubWebhookSecret" class="text-sm font-medium text-slate-200">GITHUB_WEBHOOK_SECRET</label>
                        <span id="githubSecretBadge" class="rounded-md px-2 py-1 text-xs {{ $githubSecretConfigured ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                            {{ $githubSecretConfigured ? 'Configured' : 'Missing' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="githubWebhookSecret"
                            class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 font-mono text-xs text-slate-200 focus:border-indigo-500 focus:outline-none"
                            value="{{ $githubSecret }}"
                            readonly
                            spellcheck="false"
                        />
                        <button
                            type="button"
                            data-copy-provider="github"
                            class="group rounded-lg border border-slate-700 bg-slate-900 p-2 text-slate-300 transition duration-200 hover:border-indigo-500 hover:text-indigo-200"
                            title="Copy GitHub secret"
                            aria-label="Copy GitHub secret"
                        >
                            <svg class="h-4 w-4 transition duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7 5h8a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3Z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            data-generate-provider="github"
                            class="group rounded-lg border border-indigo-500/40 bg-indigo-500/10 p-2 text-indigo-200 transition duration-200 hover:bg-indigo-500/20"
                            title="Generate GitHub secret if missing"
                            aria-label="Generate GitHub secret"
                        >
                            <svg class="h-4 w-4 transition duration-200 group-hover:rotate-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75A2.25 2.25 0 0 0 14.25 4.5h-4.5A2.25 2.25 0 0 0 7.5 6.75v3.75m9 0h.75A2.25 2.25 0 0 1 19.5 12.75v5.25a2.25 2.25 0 0 1-2.25 2.25h-10.5A2.25 2.25 0 0 1 4.5 18v-5.25A2.25 2.25 0 0 1 6.75 10.5h9.75Z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" data-feedback-provider="github">Use copy or generate to manage this value.</p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950/70 p-4" data-provider-card="gitlab">
                    <div class="mb-2 flex items-center justify-between">
                        <label for="gitlabWebhookSecret" class="text-sm font-medium text-slate-200">GITLAB_WEBHOOK_SECRET</label>
                        <span id="gitlabSecretBadge" class="rounded-md px-2 py-1 text-xs {{ $gitlabSecretConfigured ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                            {{ $gitlabSecretConfigured ? 'Configured' : 'Missing' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="gitlabWebhookSecret"
                            class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 font-mono text-xs text-slate-200 focus:border-indigo-500 focus:outline-none"
                            value="{{ $gitlabSecret }}"
                            readonly
                            spellcheck="false"
                        />
                        <button
                            type="button"
                            data-copy-provider="gitlab"
                            class="group rounded-lg border border-slate-700 bg-slate-900 p-2 text-slate-300 transition duration-200 hover:border-indigo-500 hover:text-indigo-200"
                            title="Copy GitLab secret"
                            aria-label="Copy GitLab secret"
                        >
                            <svg class="h-4 w-4 transition duration-200 group-hover:scale-110" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-7 5h8a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3Z" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            data-generate-provider="gitlab"
                            class="group rounded-lg border border-indigo-500/40 bg-indigo-500/10 p-2 text-indigo-200 transition duration-200 hover:bg-indigo-500/20"
                            title="Generate GitLab secret if missing"
                            aria-label="Generate GitLab secret"
                        >
                            <svg class="h-4 w-4 transition duration-200 group-hover:rotate-90" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75A2.25 2.25 0 0 0 14.25 4.5h-4.5A2.25 2.25 0 0 0 7.5 6.75v3.75m9 0h.75A2.25 2.25 0 0 1 19.5 12.75v5.25a2.25 2.25 0 0 1-2.25 2.25h-10.5A2.25 2.25 0 0 1 4.5 18v-5.25A2.25 2.25 0 0 1 6.75 10.5h9.75Z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500" data-feedback-provider="gitlab">Use copy or generate to manage this value.</p>
                </div>
            </div>
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

    <script>
        (() => {
            const generateUrl = @json(route('admin.webhooks.configuration.generate-secrets'));
            const csrfToken = @json(csrf_token());

            const secretInputByProvider = {
                github: document.getElementById('githubWebhookSecret'),
                gitlab: document.getElementById('gitlabWebhookSecret'),
            };

            const secretBadgeByProvider = {
                github: document.getElementById('githubSecretBadge'),
                gitlab: document.getElementById('gitlabSecretBadge'),
            };

            const dynamicStatusBanner = document.getElementById('dynamicStatusBanner');
            const serverStatusBanner = document.getElementById('serverStatusBanner');

            const feedbackFor = (provider) => document.querySelector(`[data-feedback-provider="${provider}"]`);
            const cardFor = (provider) => document.querySelector(`[data-provider-card="${provider}"]`);

            const setBadgeConfigured = (provider, configured) => {
                const badge = secretBadgeByProvider[provider];

                if (!badge) {
                    return;
                }

                badge.textContent = configured ? 'Configured' : 'Missing';
                badge.classList.remove('bg-emerald-500/20', 'text-emerald-200', 'bg-amber-500/20', 'text-amber-200');
                badge.classList.add(configured ? 'bg-emerald-500/20' : 'bg-amber-500/20');
                badge.classList.add(configured ? 'text-emerald-200' : 'text-amber-200');
            };

            const flashCard = (provider) => {
                const card = cardFor(provider);

                if (!card) {
                    return;
                }

                card.classList.add('scale-[1.01]', 'border-indigo-500/50');

                window.setTimeout(() => {
                    card.classList.remove('scale-[1.01]', 'border-indigo-500/50');
                }, 260);
            };

            const setFeedback = (provider, text, type = 'muted') => {
                const feedback = feedbackFor(provider);

                if (!feedback) {
                    return;
                }

                feedback.textContent = text;
                feedback.classList.remove('text-slate-500', 'text-emerald-300', 'text-rose-300', 'opacity-70');
                feedback.classList.add(type === 'error' ? 'text-rose-300' : type === 'success' ? 'text-emerald-300' : 'text-slate-500');
                feedback.classList.add('transition', 'duration-200');
                feedback.classList.remove('opacity-70');

                window.setTimeout(() => {
                    feedback.classList.add('opacity-70');
                }, 1600);
            };

            const showBanner = (message, type = 'success') => {
                if (!dynamicStatusBanner) {
                    return;
                }

                if (serverStatusBanner) {
                    serverStatusBanner.classList.add('hidden');
                }

                dynamicStatusBanner.textContent = message;
                dynamicStatusBanner.classList.remove('hidden');
                dynamicStatusBanner.classList.remove('border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-200', 'border-rose-500/30', 'bg-rose-500/10', 'text-rose-200');

                if (type === 'error') {
                    dynamicStatusBanner.classList.add('border-rose-500/30', 'bg-rose-500/10', 'text-rose-200');
                } else {
                    dynamicStatusBanner.classList.add('border-emerald-500/30', 'bg-emerald-500/10', 'text-emerald-200');
                }
            };

            const applyResponseState = (payload) => {
                const secrets = payload?.secrets ?? {};
                const configured = payload?.configured ?? {};

                for (const provider of ['github', 'gitlab']) {
                    if (typeof secrets[provider] === 'string' && secretInputByProvider[provider]) {
                        secretInputByProvider[provider].value = secrets[provider];
                    }

                    setBadgeConfigured(provider, Boolean(configured[provider]));
                }
            };

            const animateActionButton = (button) => {
                button.classList.add('scale-95');

                window.setTimeout(() => {
                    button.classList.remove('scale-95');
                }, 180);
            };

            const generateSecret = async (provider, button) => {
                if (!button) {
                    return;
                }

                animateActionButton(button);
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    const response = await fetch(generateUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ provider }),
                    });

                    if (!response.ok) {
                        throw new Error('Secret generation request failed.');
                    }

                    const payload = await response.json();

                    applyResponseState(payload);
                    showBanner(payload.message ?? 'Webhook secret updated.', 'success');
                    setFeedback(provider, 'Secret check completed.', 'success');
                    flashCard(provider);
                } catch (error) {
                    showBanner('Unable to generate secret right now. Please retry.', 'error');
                    setFeedback(provider, 'Failed to generate secret.', 'error');
                } finally {
                    button.disabled = false;
                    button.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            };

            const copySecret = async (provider, button) => {
                const input = secretInputByProvider[provider];

                if (!input || input.value.trim() === '') {
                    setFeedback(provider, 'No secret available to copy.', 'error');
                    return;
                }

                animateActionButton(button);

                try {
                    await navigator.clipboard.writeText(input.value);
                    setFeedback(provider, 'Copied to clipboard.', 'success');
                    flashCard(provider);
                } catch (error) {
                    input.select();
                    document.execCommand('copy');
                    setFeedback(provider, 'Copied to clipboard.', 'success');
                    flashCard(provider);
                }
            };

            document.querySelectorAll('[data-generate-provider]').forEach((button) => {
                button.addEventListener('click', () => {
                    const provider = button.getAttribute('data-generate-provider');

                    if (provider !== 'github' && provider !== 'gitlab') {
                        return;
                    }

                    generateSecret(provider, button);
                });
            });

            document.querySelectorAll('[data-copy-provider]').forEach((button) => {
                button.addEventListener('click', () => {
                    const provider = button.getAttribute('data-copy-provider');

                    if (provider !== 'github' && provider !== 'gitlab') {
                        return;
                    }

                    copySecret(provider, button);
                });
            });
        })();
    </script>
@endsection
