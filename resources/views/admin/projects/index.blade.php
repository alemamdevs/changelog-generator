@extends('layouts.admin')

@section('content')
    <div class="mb-6 rounded-3xl border border-slate-800 bg-linear-to-br from-slate-900 via-slate-900 to-slate-950 p-6 shadow-2xl shadow-slate-950/40">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-300">User dashboard</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">Your project dashboard</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">Create new projects, manage webhook setup, and review only the release history that belongs to your account.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.webhooks.configuration') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800">Setup Webhooks</a>
                <a href="#create-project" data-scroll-target="create-project" onclick="document.getElementById('create-project')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;" class="rounded-lg border border-indigo-500/50 bg-indigo-500/10 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/20">Create Project</a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Projects</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $projectCount }}</p>
                <p class="mt-1 text-sm text-slate-400">Repositories connected to your account.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Releases</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $releaseCount }}</p>
                <p class="mt-1 text-sm text-slate-400">Generated changelogs across your projects.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Latest release</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ $latestRelease?->version ?? '—' }}</p>
                <p class="mt-1 text-sm text-slate-400">{{ $latestRelease?->repository_full_name ?? 'No releases generated yet.' }}</p>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div id="create-project" tabindex="-1" class="mb-6 scroll-mt-24 rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-2xl shadow-slate-950/40">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-medium text-white">Create Project</h2>
                <p class="mt-1 text-sm text-slate-400">Add a repository in <code>owner/name</code> format to start tracking releases for your account.</p>
            </div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Only you can see these projects</p>
        </div>

        <form action="{{ route('admin.projects.store') }}" method="POST" class="mt-5 grid gap-4 md:grid-cols-3">
            @csrf
            <div>
                <label for="name" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-400">Display Name</label>
                <input id="name" name="name" value="{{ old('name') }}" placeholder="My Product" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none" />
                @error('name')
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="github_repo" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-400">Repository</label>
                <input id="github_repo" name="github_repo" value="{{ old('github_repo') }}" required placeholder="owner/repository" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none" />
                @error('github_repo')
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="default_branch" class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-400">Default Branch</label>
                <div class="flex gap-2">
                    <input id="default_branch" name="default_branch" value="{{ old('default_branch', 'main') }}" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none" />
                    <button type="submit" class="rounded-lg border border-indigo-500/60 bg-indigo-500/20 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/30">Create</button>
                </div>
                @error('default_branch')
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/40">
        @if($projects->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900">
                        <tr class="text-left text-slate-300">
                            <th class="px-4 py-3 font-medium">Project</th>
                            <th class="px-4 py-3 font-medium">Repository</th>
                            <th class="px-4 py-3 font-medium">Releases</th>
                            <th class="px-4 py-3 font-medium">Latest</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-slate-200">
                        @foreach($projects as $project)
                            <tr class="hover:bg-slate-800/40">
                                <td class="px-4 py-4">
                                    <p class="font-medium text-white">{{ $project->name ?: \Illuminate\Support\Str::after($project->github_repo, '/') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Branch: {{ $project->default_branch }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-300">{{ $project->github_repo }}</td>
                                <td class="px-4 py-4 text-slate-300">{{ $project->releases_count }}</td>
                                <td class="px-4 py-4 text-slate-400">{{ $project->latestRelease?->version ?? 'n/a' }}</td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800">View History</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-start gap-4 px-6 py-12 text-left sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl">
                    <h2 class="text-xl font-semibold text-white">No projects yet</h2>
                    <p class="mt-2 text-sm text-slate-400">Create your first project to start collecting release history. Webhooks and releases stay scoped to your account.</p>
                </div>
                <a href="#create-project" data-scroll-target="create-project" onclick="document.getElementById('create-project')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); return false;" class="rounded-lg border border-indigo-500/50 bg-indigo-500/10 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/20">Create your first project</a>
            </div>
        @endif

        @if($projects->count() > 0)
            <div class="border-t border-slate-800 px-4 py-3 text-slate-300">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@endsection
