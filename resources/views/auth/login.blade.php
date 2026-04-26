@extends('layouts.app')

@section('content')
	<div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl shadow-slate-950/50">
		<div class="mb-6">
			<p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-300">Welcome back</p>
			<h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">Sign in to your account</h1>
			<p class="mt-2 text-sm leading-6 text-slate-400">Use your email and password to access your projects.</p>
		</div>

		<form method="POST" action="{{ route('login.store') }}" class="space-y-4">
			@csrf

			<div>
				<label for="email" class="mb-1 block text-sm font-medium text-slate-200">Email</label>
				<input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-indigo-500" />
				@error('email')
					<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
				@enderror
			</div>

			<div>
				<label for="password" class="mb-1 block text-sm font-medium text-slate-200">Password</label>
				<input id="password" name="password" type="password" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-indigo-500" />
				@error('password')
					<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
				@enderror
			</div>

			<div class="flex items-center justify-between">
				<label class="flex items-center gap-2 text-sm text-slate-300">
					<input type="checkbox" name="remember" value="1" class="rounded border-slate-600 bg-slate-950 text-indigo-500 focus:ring-indigo-500" />
					Remember me
				</label>

				<a href="{{ route('register') }}" class="text-sm text-indigo-300 hover:text-indigo-200">Need an account?</a>
			</div>

			<button type="submit" class="w-full rounded-xl bg-indigo-500 px-4 py-3 font-medium text-white transition hover:bg-indigo-400">Sign in</button>
		</form>
	</div>
@endsection
