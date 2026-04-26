<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{{ config('app.name', 'Laravel') }}</title>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
	<div class="flex min-h-screen items-center justify-center px-6 py-12">
		<div class="w-full max-w-md">
			@yield('content')
		</div>
	</div>
</body>
</html>
