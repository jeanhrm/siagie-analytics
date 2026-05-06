@extends('layouts.app')
@section('title', 'Planes de Mejora')
@section('subtitle', 'Planes generados para tu institución')

@section('content')

@if($plans->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-8 text-center">
    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 mb-2">Sin planes aún</h3>
    <p class="text-sm text-gray-400 mb-5">Genera un análisis IA primero para obtener tu plan de mejora</p>
    <a href="{{ route('analysis.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
        Ver análisis
    </a>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Planes generados</h3>
        <span class="text-xs text-gray-400">{{ $plans->count() }} plan(es)</span>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($plans as $plan)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">{{ $plan->title }}</p>
                <p class="text-xs text-gray-400">{{ $plan->academic_year }} · {{ $plan->created_at->diffForHumans() }}</p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full font-medium
                {{ $plan->status === 'active' ? 'bg-blue-100 text-blue-700' : ($plan->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500') }}">
                {{ ucfirst($plan->status) }}
            </span>
            <a href="{{ route('plans.show', $plan) }}" class="text-xs bg-green-600 text-white px-3 py-1.5 rounded-lg font-medium hover:bg-green-700 transition-all">
                Ver plan
            </a>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection