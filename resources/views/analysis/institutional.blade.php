@extends('layouts.app')
@section('title', 'Análisis Institucional')
@section('subtitle', 'Análisis consolidado de toda la institución educativa')

@section('content')

{{-- Selector de uploads --}}
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
        Generar análisis institucional
    </h3>
    <p class="text-sm text-gray-500 mb-5">Selecciona los archivos de todos los salones para generar un análisis consolidado de toda la IE.</p>

    <form action="{{ route('analysis.institutional.generate') }}" method="POST">
        @csrf
        <div class="space-y-2 mb-5">
            @forelse($uploads as $upload)
            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" name="upload_ids[]" value="{{ $upload->id }}"
                    class="w-4 h-4 text-blue-600 rounded" checked>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ $upload->original_name }}</p>
                    <p class="text-xs text-gray-400">{{ $upload->academic_year }} · {{ $upload->total_rows }} registros · {{ $upload->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Procesado</span>
            </label>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">No hay archivos cargados aún.</p>
            @endforelse
        </div>

        @if($uploads->count() > 0)
        <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Año académico</label>
                <select name="academic_year" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Contexto institucional</label>
                <select name="context" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="rural_secundaria">Rural — Secundaria</option>
                    <option value="rural_primaria">Rural — Primaria</option>
                    <option value="urbana_secundaria">Urbana — Secundaria</option>
                    <option value="urbana_primaria">Urbana — Primaria</option>
                </select>
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
            </svg>
            Generar Análisis Institucional con IA
        </button>
        @endif
    </form>
</div>

{{-- Reportes institucionales previos --}}
@if($reports->count() > 0)
<div class="bg-white rounded-2xl border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-900">Análisis institucionales generados</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($reports as $report)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 5h1"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">Análisis Institucional — {{ $report->academic_year }}</p>
                <p class="text-xs text-gray-400">{{ $report->created_at->diffForHumans() }}</p>
            </div>
            <a href="{{ route('analysis.show', $report) }}" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg font-medium hover:bg-blue-700 transition-all">
                Ver análisis
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection