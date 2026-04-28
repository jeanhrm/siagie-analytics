@extends('layouts.app')
@section('title', 'Análisis IA')
@section('subtitle', 'Reportes de análisis generados')

@section('content')

@if($reports->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-8 text-center">
    <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 mb-2">Sin análisis aún</h3>
    <p class="text-sm text-gray-400 mb-5">Carga un archivo Excel y genera tu primer análisis con IA</p>
    <a href="{{ route('uploads.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
        Cargar archivo Excel
    </a>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-900">Reportes generados</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($reports as $report)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $report->upload->original_name }}</p>
                <p class="text-xs text-gray-400">{{ $report->academic_year }} · {{ $report->created_at->diffForHumans() }}</p>
            </div>
            <a href="{{ route('analysis.show', $report) }}" class="text-xs bg-purple-600 text-white px-3 py-1.5 rounded-lg font-medium hover:bg-purple-700 transition-all">
                Ver análisis
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection