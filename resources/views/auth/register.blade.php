@extends('layouts.app')

@section('content')
	<div class="rounded-3xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl shadow-slate-950/50">
		<div class="mb-6">
			<p class="text-xs font-semibold uppercase tracking-[0.25em] text-indigo-300">Get started</p>
			<h1 class="mt-3 text-3xl font-semibold tracking-tight text-white">Create your account</h1>
			<p class="mt-2 text-sm leading-6 text-slate-400">Set up your workspace and start managing multiple projects.</p>
		</div>

		<form method="POST" action="{{ route('register.store') }}" class="space-y-4">
			@csrf

			<div>
				<label for="name" class="mb-1 block text-sm font-medium text-slate-200">Name</label>
				<input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-indigo-500" />
				@error('name')
					<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
				@enderror
			</div>

			<div>
				<label for="email" class="mb-1 block text-sm font-medium text-slate-200">Email</label>
				<input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-indigo-500" />
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

			<div>
				<label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-200">Confirm password</label>
				<input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-slate-100 outline-none ring-0 placeholder:text-slate-500 focus:border-indigo-500" />
			</div>

			<button type="submit" class="w-full rounded-xl bg-indigo-500 px-4 py-3 font-medium text-white transition hover:bg-indigo-400">Create account</button>
		</form>

		<p class="mt-6 text-center text-sm text-slate-400">
			Already have an account?
			<a href="{{ route('login') }}" class="text-indigo-300 hover:text-indigo-200">Sign in</a>
		</p>
	</div>
@endsection
