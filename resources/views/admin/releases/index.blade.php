@extends('layouts.admin')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-white">Releases</h1>
            <p class="mt-1 text-sm text-slate-400">Generated changelogs with semantic versioning.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 shadow-2xl shadow-slate-950/40">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-900">
                    <tr class="text-left text-slate-300">
                        <th class="px-4 py-3 font-medium">Version</th>
                        <th class="px-4 py-3 font-medium">Repository</th>
                        <th class="px-4 py-3 font-medium">Branch</th>
                        <th class="px-4 py-3 font-medium">Generated</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 text-slate-200">
                    @forelse($releases as $release)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3"><span class="rounded-md bg-indigo-500/20 px-2 py-1 text-xs font-medium text-indigo-200">{{ $release->version }}</span></td>
                            <td class="px-4 py-3 text-slate-300">{{ $release->repository_full_name ?? 'n/a' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $release->branch }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ optional($release->generated_at)->toDayDateTimeString() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.releases.show', $release) }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="5">No releases found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-800 px-4 py-3 text-slate-300">
            {{ $releases->links() }}
        </div>
    </div>
@endsection
