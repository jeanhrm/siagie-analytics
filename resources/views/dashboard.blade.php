@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Resumen general de tu institución educativa')

@section('content')

{{-- Métricas principales --}}
<div class="grid grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Estudiantes</p>
        <p class="text-3xl font-semibold text-gray-900">{{ $totalStudents ?? '—' }}</p>
        <p class="text-xs text-gray-400 mt-1">
            {{ $totalStudents ? 'en último análisis' : 'Sin datos aún' }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Aprobación</p>
        <p class="text-3xl font-semibold {{ $approvalRate ? ($approvalRate >= 60 ? 'text-green-600' : 'text-amber-500') : 'text-gray-900' }}">
            {{ $approvalRate ? $approvalRate . '%' : '—' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">
            {{ $approvalRate ? 'logro esperado + destacado' : 'Sin datos aún' }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">En Riesgo</p>
        <p class="text-3xl font-semibold {{ $atRiskCount ? 'text-red-500' : 'text-gray-900' }}">
            {{ $atRiskCount ?? '—' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">
            {{ $atRiskPct ? $atRiskPct . '% del total' : 'Sin datos aún' }}
        </p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-3">Archivos</p>
        <p class="text-3xl font-semibold text-gray-900">{{ $totalUploads }}</p>
        <p class="text-xs text-gray-400 mt-1">Excel cargados</p>
    </div>
</div>

@if($latestReport)
{{-- Áreas críticas --}}
@if(!empty($criticalAreas))
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
            Áreas críticas identificadas
        </h3>
        <a href="{{ route('analysis.show', $latestReport) }}" class="text-xs text-blue-600 hover:underline">
            Ver análisis completo →
        </a>
    </div>
    <div class="grid grid-cols-3 gap-3">
        @foreach(array_slice($criticalAreas, 0, 3) as $area)
        <div class="p-3 bg-red-50 rounded-xl border border-red-100">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-semibold text-red-800">{{ $area['area'] }}</p>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                    {{ $area['severity'] === 'alta' ? 'bg-red-200 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                    {{ ucfirst($area['severity']) }}
                </span>
            </div>
            <p class="text-xs text-red-600 leading-tight">{{ Str::limit($area['description'], 80) }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Plan activo --}}
@if($latestPlan)
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            Plan de mejora activo
        </h3>
        <a href="{{ route('plans.show', $latestPlan) }}" class="text-xs text-blue-600 hover:underline">
            Ver plan completo →
        </a>
    </div>
    <p class="text-sm text-gray-600 mb-3">{{ $latestPlan->title }}</p>
    <div class="flex items-center gap-3">
        <span class="text-xs px-3 py-1 rounded-full font-medium
            {{ $latestPlan->status === 'active' ? 'bg-blue-100 text-blue-700' : ($latestPlan->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500') }}">
            {{ ucfirst($latestPlan->status) }}
        </span>
        <span class="text-xs text-gray-400">{{ count($latestPlan->axes) }} ejes estratégicos</span>
    </div>
</div>
@endif

{{-- Uploads recientes --}}
@if($recentUploads->isNotEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900">Archivos recientes</h3>
        <a href="{{ route('uploads.index') }}" class="text-xs text-blue-600 hover:underline">Ver todos →</a>
    </div>
    <div class="space-y-2">
        @foreach($recentUploads as $upload)
        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
            <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-green-700 font-mono">XLS</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-900 truncate">{{ $upload->original_name }}</p>
                <p class="text-xs text-gray-400">{{ $upload->academic_year }} · {{ $upload->created_at->diffForHumans() }}</p>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ $upload->total_rows }} reg.</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@else
{{-- Estado vacío --}}
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
@endif

@endsection