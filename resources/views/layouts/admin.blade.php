<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auto Changelog Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-800/80 bg-slate-900/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-white">Auto Changelog</a>
            <nav class="flex items-center gap-2 text-sm">
                <a href="{{ route('admin.releases.index') }}" class="rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">Releases</a>
                <a href="{{ route('admin.webhooks.index') }}" class="rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">Webhooks</a>
                <a href="{{ route('admin.webhooks.configuration') }}" class="rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white">Configuration</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-8">
        @yield('content')
    </main>
</body>
</html>
