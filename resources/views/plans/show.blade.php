@extends('layouts.app')
@section('title', 'Plan de Mejora')
@section('subtitle', $plan->title)

@section('content')

{{-- Narrativa --}}
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">Plan de Mejora</span>
        <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full">{{ $plan->academic_year }}</span>
        <span class="ml-auto text-xs px-3 py-1 rounded-full font-medium
            {{ $plan->status === 'active' ? 'bg-blue-100 text-blue-700' : ($plan->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500') }}">
            {{ ucfirst($plan->status) }}
        </span>
    </div>
    <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $plan->ai_narrative }}</div>
</div>

{{-- Ejes estratégicos --}}
@foreach($plan->axes as $eje)
@php
    $colors = [
        'red'   => ['bg' => 'bg-red-50', 'border' => 'border-red-100', 'badge' => 'bg-red-100 text-red-700', 'num' => 'bg-red-500', 'dot' => 'bg-red-500'],
        'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-100', 'badge' => 'bg-amber-100 text-amber-700', 'num' => 'bg-amber-500', 'dot' => 'bg-amber-500'],
        'green' => ['bg' => 'bg-green-50', 'border' => 'border-green-100', 'badge' => 'bg-green-100 text-green-700', 'num' => 'bg-green-500', 'dot' => 'bg-green-500'],
    ];
    $c = $colors[$eje['color']] ?? $colors['amber'];
    $priorityLabel = ['urgente' => 'URGENTE', 'importante' => 'IMPORTANTE', 'potenciar' => 'POTENCIAR'];
@endphp
<div class="bg-white rounded-2xl border border-gray-100 mb-4 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
        <div class="w-8 h-8 {{ $c['num'] }} rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
            {{ $eje['number'] }}
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-900">{{ $eje['title'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $eje['description'] }}</p>
        </div>
        <span class="text-xs px-3 py-1 rounded-full font-medium {{ $c['badge'] }}">
            {{ $priorityLabel[$eje['priority']] ?? strtoupper($eje['priority']) }}
        </span>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($eje['actions'] as $idx => $action)
        <div class="px-6 py-3 flex items-start gap-3">
            <div class="w-5 h-5 rounded border-2 border-gray-200 flex-shrink-0 mt-0.5
                {{ $action['done'] ? 'bg-green-500 border-green-500' : '' }}">
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-900">{{ $action['action'] }}</p>
                <div class="flex items-center gap-3 mt-1 flex-wrap">
                    <span class="text-xs text-gray-400">👤 {{ $action['responsible'] }}</span>
                    <span class="text-xs text-gray-400">📅 {{ $action['deadline'] }}</span>
                    <span class="text-xs text-gray-400">🛠 {{ $action['resources'] }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

{{-- Acciones --}}
<div class="flex gap-3 mt-4">
    @if($plan->status === 'draft')
    <form action="{{ route('plans.update', $plan) }}" method="POST">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="active">
        <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
            Activar plan
        </button>
    </form>
    @elseif($plan->status === 'active')
    <form action="{{ route('plans.update', $plan) }}" method="POST">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="completed">
        <button type="submit" class="bg-green-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-green-700 transition-all">
            Marcar completado
        </button>
    </form>
    @endif
    <a href="{{ route('plans.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
        Ver todos los planes
    </a>
</div>

@endsection