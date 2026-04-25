@extends('layouts.admin')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Projects</h1>
            <p class="mt-1 text-sm text-slate-400">Manage multiple repositories and browse their release history.</p>
        </div>
        <a href="{{ route('admin.webhooks.configuration') }}" class="rounded-lg border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800">Setup Webhooks</a>
    </div>

    @if(session('status'))
        <div class="mb-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
        <h2 class="text-lg font-medium text-white">Setup Project</h2>
        <p class="mt-1 text-sm text-slate-400">Add a repository in <code>owner/name</code> format to track its releases.</p>

        <form action="{{ route('admin.projects.store') }}" method="POST" class="mt-4 grid gap-4 md:grid-cols-3">
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
                    <button type="submit" class="rounded-lg border border-indigo-500/60 bg-indigo-500/20 px-4 py-2 text-sm font-medium text-indigo-100 hover:bg-indigo-500/30">Add</button>
                </div>
                @error('default_branch')
                    <p class="mt-1 text-xs text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/40">
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
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                <p class="font-medium text-white">{{ $project->name ?: \Illuminate\Support\Str::after($project->github_repo, '/') }}</p>
                                <p class="text-xs text-slate-500">{{ $project->default_branch }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $project->github_repo }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $project->releases_count }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $project->latestRelease?->version ?? 'n/a' }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.projects.show', $project) }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800">View History</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="5">No projects found. Add your first project above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-800 px-4 py-3 text-slate-300">
            {{ $projects->links() }}
        </div>
    </div>
@endsection
