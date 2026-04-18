@extends('layouts.admin')

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.webhooks.index') }}" class="text-sm text-slate-400 hover:text-white">← Back to webhooks</a>
        <h1 class="mt-2 text-2xl font-semibold text-white">Webhook Delivery #{{ $delivery->id }}</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 class="text-lg font-medium text-white">Delivery Details</h2>
                <dl class="mt-4 grid grid-cols-1 gap-3 text-sm text-slate-300 md:grid-cols-2">
                    <div><dt class="text-slate-400">Provider</dt><dd>{{ $delivery->provider }}</dd></div>
                    <div><dt class="text-slate-400">Event</dt><dd>{{ $delivery->event }}</dd></div>
                    <div><dt class="text-slate-400">Repository</dt><dd>{{ $delivery->repository_full_name }}</dd></div>
                    <div><dt class="text-slate-400">Ref</dt><dd>{{ $delivery->ref }}</dd></div>
                    <div><dt class="text-slate-400">Status</dt><dd>{{ $delivery->status }}</dd></div>
                    <div><dt class="text-slate-400">Signature Valid</dt><dd>{{ $delivery->signature_valid ? 'Yes' : 'No' }}</dd></div>
                    <div><dt class="text-slate-400">Processed At</dt><dd>{{ optional($delivery->processed_at)->toDayDateTimeString() ?? 'n/a' }}</dd></div>
                </dl>

                @if($delivery->error_message)
                    <div class="mt-4 rounded-lg border border-rose-500/40 bg-rose-500/10 p-3 text-sm text-rose-200">
                        {{ $delivery->error_message }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 class="text-lg font-medium text-white">Payload</h2>
                <pre class="mt-3 max-h-[420px] overflow-auto rounded-lg border border-slate-800 bg-slate-950 p-3 text-xs text-slate-200">{{ json_encode($delivery->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </section>

        <aside>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 class="text-lg font-medium text-white">Related Release</h2>

                @if($delivery->release)
                    <p class="mt-3 text-sm text-slate-300">Version: <span class="font-medium text-white">{{ $delivery->release->version }}</span></p>
                    <a href="{{ route('admin.releases.show', $delivery->release) }}" class="mt-4 inline-flex rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-200 hover:bg-slate-800">Open Release</a>
                @else
                    <p class="mt-3 text-sm text-slate-400">No release generated for this delivery.</p>
                @endif
            </div>
        </aside>
    </div>
@endsection
