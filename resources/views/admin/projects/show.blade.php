@extends('layouts.admin')

@section('content')
    <div class="mb-6 rounded-3xl border border-slate-800 bg-linear-to-br from-slate-900 via-slate-900 to-slate-950 p-6 shadow-2xl shadow-slate-950/40">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <a href="{{ route('admin.projects.index') }}" class="text-sm text-slate-400 hover:text-white">← Back to projects</a>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">{{ $project->name ?: $project->github_repo }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">{{ $project->github_repo }} · default branch {{ $project->default_branch }} · only your release history is shown here.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.webhooks.configuration') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800">Setup Webhooks</a>
                <a href="{{ route('admin.projects.index') }}#create-project" class="rounded-lg border border-indigo-500/50 bg-indigo-500/10 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/20">Create Project</a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Releases</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $project->releases_count }}</p>
                <p class="mt-1 text-sm text-slate-400">Release history for this project.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Latest release</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $latestRelease?->version ?? '—' }}</p>
                <p class="mt-1 text-sm text-slate-400">{{ optional($latestRelease?->generated_at)->toDayDateTimeString() ?? 'No releases generated yet.' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Branch</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $project->default_branch }}</p>
                <p class="mt-1 text-sm text-slate-400">Tracked by incoming webhook events.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Repository</p>
                <p class="mt-2 truncate text-2xl font-semibold text-white">{{ $project->github_repo }}</p>
                <p class="mt-1 text-sm text-slate-400">Scoped to your account.</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/40">
        <div class="border-b border-slate-800 px-4 py-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-medium text-white">Release history</h2>
                    <p class="mt-1 text-sm text-slate-400">Every release listed below belongs to this project and your account.</p>
                </div>
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $releases->total() }} total releases</p>
            </div>
        </div>

        @if($releases->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900">
                        <tr class="text-left text-slate-300">
                            <th class="px-4 py-3 font-medium">Version</th>
                            <th class="px-4 py-3 font-medium">Branch</th>
                            <th class="px-4 py-3 font-medium">Generated</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-slate-200">
                        @foreach($releases as $release)
                            <tr class="hover:bg-slate-800/40">
                                <td class="px-4 py-4"><span class="rounded-md bg-indigo-500/20 px-2 py-1 text-xs font-medium text-indigo-200">{{ $release->version }}</span></td>
                                <td class="px-4 py-4 text-slate-300">{{ $release->branch }}</td>
                                <td class="px-4 py-4 text-slate-400">{{ optional($release->generated_at)->toDayDateTimeString() }}</td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.releases.show', $release) }}" class="inline-flex rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-start gap-4 px-6 py-12 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <h3 class="text-xl font-semibold text-white">No release history yet</h3>
                    <p class="mt-2 text-sm text-slate-400">Once your webhook is connected, new pushes will generate releases and populate this project history automatically.</p>
                </div>
                <a href="{{ route('admin.webhooks.configuration') }}" class="rounded-lg border border-indigo-500/50 bg-indigo-500/10 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/20">Setup webhooks</a>
            </div>
        @endif

        @if($releases->count() > 0)
            <div class="border-t border-slate-800 px-4 py-3 text-slate-300">
                {{ $releases->links() }}
            </div>
        @endif
    </div>
@endsection
