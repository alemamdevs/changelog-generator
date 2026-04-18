@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">Webhook Deliveries</h1>
        <p class="mt-1 text-sm text-slate-400">Monitor GitHub push events and processing status.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr class="text-left text-slate-300">
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Repository</th>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Release</th>
                        <th class="px-4 py-3">Received</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-200">
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td class="px-4 py-3">#{{ $delivery->id }}</td>
                            <td class="px-4 py-3">{{ $delivery->repository_full_name }}</td>
                            <td class="px-4 py-3">{{ $delivery->event }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs font-medium {{ in_array($delivery->status, ['processed', 'duplicate']) ? 'bg-emerald-500/20 text-emerald-200' : (in_array($delivery->status, ['failed']) ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-200') }}">
                                    {{ $delivery->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $delivery->release?->version ?? 'n/a' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ optional($delivery->created_at)->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.webhooks.show', $delivery) }}" class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs hover:bg-slate-800">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-slate-400" colspan="7">No webhook deliveries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-800 px-4 py-3">
            {{ $deliveries->links() }}
        </div>
    </div>
@endsection
