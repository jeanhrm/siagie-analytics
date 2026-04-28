@extends('layouts.app')
@section('title', 'Análisis IA')
@section('subtitle', 'Resultados del análisis — ' . $report->upload->original_name)

@section('content')

{{-- Métricas rápidas --}}
<div class="grid grid-cols-3 gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Total Registros</p>
        <p class="text-3xl font-semibold text-gray-900">{{ $report->summary_data['total_students'] }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">En Riesgo</p>
        <p class="text-3xl font-semibold text-red-500">{{ $report->at_risk_students['count'] ?? '—' }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $report->at_risk_students['percentage'] ?? '' }}% del total</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Año Académico</p>
        <p class="text-3xl font-semibold text-blue-600">{{ $report->academic_year }}</p>
    </div>
</div>

{{-- Análisis IA --}}
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-medium">Análisis Claude</span>
        <h3 class="text-sm font-semibold text-gray-900">Diagnóstico general</h3>
    </div>
    <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $report->ai_analysis }}</div>
</div>

{{-- Áreas críticas y fortalezas --}}
<div class="grid grid-cols-2 gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
            Áreas Críticas
        </h3>
        @foreach($report->critical_areas ?? [] as $area)
        <div class="mb-3 p-3 bg-red-50 rounded-xl border border-red-100">
            <div class="flex items-center justify-between mb-1">
                <p class="text-sm font-medium text-red-800">{{ $area['area'] }}</p>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                    {{ $area['severity'] === 'alta' ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($area['severity']) }}
                </span>
            </div>
            <p class="text-xs text-red-600">{{ $area['description'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            Fortalezas
        </h3>
        @foreach($report->strengths ?? [] as $strength)
        <div class="mb-3 p-3 bg-green-50 rounded-xl border border-green-100">
            <p class="text-sm font-medium text-green-800 mb-1">{{ $strength['area'] }}</p>
            <p class="text-xs text-green-600">{{ $strength['description'] }}</p>
        </div>
        @endforeach
    </div>
</div>

{{-- Factores de riesgo --}}
@if(!empty($report->at_risk_students['main_factors']))
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Factores de riesgo principales</h3>
    <div class="flex flex-wrap gap-2">
        @foreach($report->at_risk_students['main_factors'] as $factor)
        <span class="text-xs bg-orange-100 text-orange-700 px-3 py-1.5 rounded-full">{{ $factor }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- Botón generar plan --}}
<div class="flex gap-3">
    <form action="{{ route('plans.generate', $report) }}" method="POST">
        @csrf
        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-xl text-sm font-medium hover:bg-green-700 transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Generar Plan de Mejora
        </button>
    </form>
    <a href="{{ route('analysis.index') }}" class="bg-gray-100 text-gray-600 px-6 py-3 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
        Ver todos los análisis
    </a>
</div>

@endsection