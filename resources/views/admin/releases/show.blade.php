@extends('layouts.admin')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ $release->project ? route('admin.projects.show', $release->project) : route('admin.projects.index') }}" class="text-sm text-slate-400 hover:text-white">← Back to project</a>
            <h1 class="mt-2 text-2xl font-semibold text-white">Release {{ $release->version }}</h1>
            <p class="mt-1 text-sm text-slate-400">{{ $release->repository_full_name }} · {{ $release->branch }}</p>
        </div>
        <div class="text-right text-xs text-slate-400">
            <p>Generated {{ optional($release->generated_at)->toDayDateTimeString() }}</p>
            <p>Tag: {{ $release->tag_name ?? 'n/a' }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 class="text-lg font-medium text-white">Structured Changelog</h2>

                @if($release->changelogs->isEmpty())
                    <p class="mt-3 text-sm text-slate-400">No changelog entries generated.</p>
                @else
                    <div class="mt-4 space-y-4">
                        @foreach($release->changelogs->groupBy('category') as $category => $items)
                            <div>
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-300">{{ $category }}</h3>
                                <ul class="mt-2 space-y-2 text-sm text-slate-200">
                                    @foreach($items as $item)
                                        <li class="rounded-lg border border-slate-800 bg-slate-900 px-3 py-2">
                                            <p>{{ $item->description }}</p>
                                            @if($item->details)
                                                <p class="mt-1 text-xs text-slate-400">{{ $item->details }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 class="text-lg font-medium text-white">Commits</h2>

                @if($release->commits->isEmpty())
                    <p class="mt-3 text-sm text-slate-400">No commits recorded.</p>
                @else
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800 text-sm">
                            <thead>
                                <tr class="text-left text-slate-300">
                                    <th class="px-3 py-2">Hash</th>
                                    <th class="px-3 py-2">Author</th>
                                    <th class="px-3 py-2">Category</th>
                                    <th class="px-3 py-2">Subject</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-200">
                                @foreach($release->commits as $commit)
                                    <tr>
                                        <td class="px-3 py-2"><code class="text-xs">{{ \Illuminate\Support\Str::limit($commit->commit_hash, 10) }}</code></td>
                                        <td class="px-3 py-2">{{ $commit->author }}</td>
                                        <td class="px-3 py-2">{{ $commit->category ?? 'n/a' }}</td>
                                        <td class="px-3 py-2">{{ $commit->subject ?? $commit->message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-medium text-white">Markdown Output</h2>
                    <button
                        id="copy-markdown-button"
                        type="button"
                        class="group inline-flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-xs font-medium text-slate-200 transition-all duration-300 hover:-translate-y-0.5 hover:border-emerald-500/60 hover:text-emerald-200 disabled:cursor-not-allowed disabled:opacity-40"
                        @disabled(blank((string) $release->markdown_content))
                    >
                        <svg id="copy-markdown-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-3.5 w-3.5 transition-transform duration-300">
                            <path d="M16 1H4a2 2 0 0 0-2 2v12h2V3h12V1Z" />
                            <path d="M19 5H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 16H8V7h11v14Z" />
                        </svg>
                        <span id="copy-markdown-label" class="transition-all duration-300">Copy</span>
                    </button>
                </div>
                <p class="mt-2 text-xs text-slate-400">Path: {{ $release->markdown_path ?? 'n/a' }}</p>
                <pre id="markdown-output" class="mt-3 max-h-105 overflow-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-xs text-slate-200">{{ $release->markdown_content }}</pre>
            </div>
        </aside>
    </div>

    <script>
        (() => {
            const copyButton = document.getElementById('copy-markdown-button');
            const markdownOutput = document.getElementById('markdown-output');
            const label = document.getElementById('copy-markdown-label');
            const icon = document.getElementById('copy-markdown-icon');

            if (!copyButton || !markdownOutput || !label || !icon) {
                return;
            }

            const defaultIcon = `
                <path d="M16 1H4a2 2 0 0 0-2 2v12h2V3h12V1Z" />
                <path d="M19 5H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Zm0 16H8V7h11v14Z" />
            `;
            const copiedIcon = `
                <path d="M12 2a10 10 0 1 0 10 10A10.01 10.01 0 0 0 12 2Zm-1.18 14.59-3.41-3.42 1.41-1.41 2 2 4.59-4.58 1.41 1.41Z" />
            `;

            const copyText = async (text) => {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            };

            let resetTimer = null;

            copyButton.addEventListener('click', async () => {
                const markdown = markdownOutput.textContent?.trim() ?? '';

                if (markdown === '') {
                    return;
                }

                try {
                    await copyText(markdown);

                    copyButton.classList.add('border-emerald-500/70', 'text-emerald-200', 'shadow-lg', 'shadow-emerald-500/20');
                    icon.classList.add('scale-110');
                    icon.innerHTML = copiedIcon;
                    label.textContent = 'Copied!';

                    if (resetTimer !== null) {
                        window.clearTimeout(resetTimer);
                    }

                    resetTimer = window.setTimeout(() => {
                        copyButton.classList.remove('border-emerald-500/70', 'text-emerald-200', 'shadow-lg', 'shadow-emerald-500/20');
                        icon.classList.remove('scale-110');
                        icon.innerHTML = defaultIcon;
                        label.textContent = 'Copy';
                    }, 1400);
                } catch (error) {
                    label.textContent = 'Failed';
                    window.setTimeout(() => {
                        icon.innerHTML = defaultIcon;
                        label.textContent = 'Copy';
                    }, 1200);
                }
            });
        })();
    </script>
@endsection
