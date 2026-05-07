<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAGIE Analytics — Iniciar sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    @endphp
    <link rel="stylesheet" href="/build/{{ $manifest['resources/css/app.css']['file'] }}">
</head>
<body class="bg-gray-50 font-sans antialiased min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900">SIAGIE <span class="text-blue-600">Analytics</span></h1>
        <p class="text-sm text-gray-400 mt-1">Análisis educativo con inteligencia artificial</p>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">

        <h2 class="text-base font-semibold text-gray-900 mb-6">Iniciar sesión</h2>

        @if(session('status'))
            <div class="mb-4 bg-green-50 text-green-700 px-4 py-3 rounded-xl text-sm">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-300 @enderror"
                    placeholder="tu@correo.com">
                @error('email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-300 @enderror"
                    placeholder="••••••••">
                @error('password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-xs text-gray-500">Recordarme</span>
                </label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:underline">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all mt-2">
                Ingresar
            </button>
        </form>

    </div>

    {{-- Footer --}}
    <p class="text-center text-xs text-gray-400 mt-6">
        Plataforma desarrollada por
        <span class="font-medium text-gray-500">Quipubit / ORBAS Consultores</span>
        — Huancavelica, Perú
    </p>

</div>

</body>
</html>