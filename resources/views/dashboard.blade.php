@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Resumen general de tu institución educativa')

@section('content')
<div class="grid grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Estudiantes</p>
        <p class="text-3xl font-semibold text-gray-900">—</p>
        <p class="text-xs text-gray-400 mt-1">Sin datos aún</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Aprobación</p>
        <p class="text-3xl font-semibold text-gray-900">—</p>
        <p class="text-xs text-gray-400 mt-1">Sin datos aún</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">En Riesgo</p>
        <p class="text-3xl font-semibold text-red-500">—</p>
        <p class="text-xs text-gray-400 mt-1">Sin datos aún</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Archivos</p>
        <p class="text-3xl font-semibold text-gray-900">0</p>
        <p class="text-xs text-gray-400 mt-1">Excel cargados</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 p-8 text-center">
    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 mb-2">Comienza cargando tu primer archivo</h3>
    <p class="text-sm text-gray-400 mb-5">Sube los Excel exportados del SIAGIE para comenzar el análisis</p>
    <a href="{{ route('uploads.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Cargar archivo Excel
    </a>
</div>
@endsection