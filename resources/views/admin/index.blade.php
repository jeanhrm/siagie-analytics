@extends('layouts.app')
@section('title', 'Panel de Administración')
@section('subtitle', 'Gestión de instituciones y usuarios')

@section('content')

<div class="grid grid-cols-2 gap-6 mb-6">

    {{-- Crear institución --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
            Nueva Institución Educativa
        </h3>
        <form action="{{ route('admin.institutions.create') }}" method="POST" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre IE</label>
                    <input type="text" name="name" required placeholder="IE 36006 Huancavelica"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Código modular</label>
                    <input type="text" name="code" required placeholder="36006"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">UGEL</label>
                    <input type="text" name="ugel" required placeholder="UGEL Huancavelica"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Distrito</label>
                    <input type="text" name="district" required placeholder="Huancavelica"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nivel</label>
                    <select name="level" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="primaria">Primaria</option>
                        <option value="secundaria">Secundaria</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Contexto</label>
                    <select name="context" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="rural">Rural</option>
                        <option value="urbana">Urbana</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Director(a)</label>
                <input type="text" name="director_name" placeholder="Nombre del director"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
                Crear institución
            </button>
        </form>
    </div>

    {{-- Crear usuario --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            Nuevo Usuario
        </h3>
        <form action="{{ route('admin.users.create') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre completo</label>
                <input type="text" name="name" required placeholder="Juan Pérez"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                <input type="email" name="email" required placeholder="usuario@correo.com"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Contraseña</label>
                <input type="password" name="password" required placeholder="Mínimo 8 caracteres"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Rol</label>
                    <select name="role" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="director">Director</option>
                        <option value="docente">Docente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución</label>
                    <select name="institution_id" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($institutions as $institution)
                        <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-green-700 transition-all">
                Crear usuario
            </button>
        </form>
    </div>

</div>

{{-- Lista de usuarios --}}
<div class="bg-white rounded-2xl border border-gray-100 mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Usuarios registrados</h3>
        <span class="text-xs text-gray-400">{{ $users->count() }} usuario(s)</span>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($users as $user)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-9 h-9 {{ $user->isDirector() ? 'bg-blue-500' : 'bg-green-500' }} rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                <p class="text-xs text-gray-400">{{ $user->email }} · {{ $user->institution->name ?? 'Sin IE' }}</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full font-medium
                {{ $user->isDirector() ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                {{ ucfirst($user->role) }}
            </span>
            <form action="{{ route('admin.users.delete', $user->id) }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" onclick="return confirm('¿Eliminar usuario?')"
                    class="text-gray-300 hover:text-red-500 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
        @empty
        <div class="p-8 text-center">
            <p class="text-sm text-gray-400">No hay usuarios aún.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Lista de instituciones --}}
<div class="bg-white rounded-2xl border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Instituciones registradas</h3>
        <span class="text-xs text-gray-400">{{ $institutions->count() }} IE(s)</span>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($institutions as $institution)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-9 h-9 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 5h1"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $institution->name }}</p>
                <p class="text-xs text-gray-400">{{ $institution->ugel }} · {{ $institution->level }} · {{ $institution->uploads_count }} archivos</p>
            </div>
            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-500 font-mono">
                {{ $institution->code }}
            </span>
        </div>
        @empty
        <div class="p-8 text-center">
            <p class="text-sm text-gray-400">No hay instituciones aún.</p>
        </div>
        @endforelse
    </div>
</div>

@endsection