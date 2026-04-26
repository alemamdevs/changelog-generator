@extends('layouts.admin')

@section('content')
    <div class="mb-6 rounded-3xl border border-slate-800 bg-slate-900/60 p-6 shadow-2xl shadow-slate-950/40">
        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-300">User dashboard</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">Releases</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">Browse generated changelogs across all of your projects. This list is automatically scoped to your account.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/40">
        @if($releases->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900">
                        <tr class="text-left text-slate-300">
                            <th class="px-4 py-3 font-medium">Version</th>
                            <th class="px-4 py-3 font-medium">Project</th>
                            <th class="px-4 py-3 font-medium">Repository</th>
                            <th class="px-4 py-3 font-medium">Branch</th>
                            <th class="px-4 py-3 font-medium">Generated</th>
                            <th class="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 text-slate-200">
                        @foreach($releases as $release)
                            <tr class="hover:bg-slate-800/40">
                                <td class="px-4 py-4"><span class="rounded-md bg-indigo-500/20 px-2 py-1 text-xs font-medium text-indigo-200">{{ $release->version }}</span></td>
                                <td class="px-4 py-4 text-slate-300">{{ $release->project?->name ?? $release->project?->github_repo ?? 'n/a' }}</td>
                                <td class="px-4 py-4 text-slate-300">{{ $release->repository_full_name ?? 'n/a' }}</td>
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
            <div class="px-6 py-12">
                <h2 class="text-xl font-semibold text-white">No releases yet</h2>
                <p class="mt-2 text-sm text-slate-400">Once your projects receive pushes, their generated releases will appear here automatically.</p>
            </div>
        @endif

        @if($releases->count() > 0)
            <div class="border-t border-slate-800 px-4 py-3 text-slate-300">
                {{ $releases->links() }}
            </div>
        @endif
    </div>
@endsection
