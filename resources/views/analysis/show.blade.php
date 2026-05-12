@extends('layouts.app')
@section('title', 'Análisis IA')
@section('subtitle', $report->type === 'institutional' 
    ? 'Análisis Institucional — ' . ($report->summary_data['general_info']['nombre_ie'] ?? auth()->user()->institution->name)
    : 'Análisis de Aula — ' . $report->upload->original_name)

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


{{-- CHART DE BARRAS POR ÁREA --}}
@if(!empty($report->summary_data['areas']))
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
            Distribución por área curricular
        </h3>
        <div class="flex items-center gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-500 rounded-sm inline-block"></span> AD</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded-sm inline-block"></span> A</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-amber-400 rounded-sm inline-block"></span> B</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 bg-red-400 rounded-sm inline-block"></span> C</span>
        </div>
    </div>
    <canvas id="areasChart" height="120"></canvas>
</div>
@endif

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

{{-- Detalle por competencia --}}
@if(!empty($report->summary_data['areas']))
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-5 flex items-center gap-2">
        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
        Resultados por área y competencia
    </h3>

    @foreach($report->summary_data['areas'] as $areaName => $area)
    <div class="mb-6 border border-gray-100 rounded-xl overflow-hidden">
        {{-- Header del área --}}
        <div class="px-4 py-3 bg-gray-50 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-900">{{ $areaName }}</p>
            <div class="flex items-center gap-3">
                <span class="text-xs font-mono text-green-700 bg-green-50 px-2 py-0.5 rounded">Logro: {{ $area['pct_logro'] }}%</span>
                <span class="text-xs font-mono text-amber-700 bg-amber-50 px-2 py-0.5 rounded">Proceso: {{ $area['pct_proceso'] }}%</span>
                <span class="text-xs font-mono text-red-700 bg-red-50 px-2 py-0.5 rounded">Inicio: {{ $area['pct_inicio'] }}%</span>
            </div>
        </div>

        {{-- Barra global del área --}}
        <div class="px-4 py-2 border-b border-gray-50">
            <div class="flex h-3 rounded-full overflow-hidden gap-0.5">
                @if($area['total_notas'] > 0)
                    @php
                        $pctAD = round($area['distribucion']['AD'] / $area['total_notas'] * 100);
                        $pctA  = round($area['distribucion']['A']  / $area['total_notas'] * 100);
                        $pctB  = round($area['distribucion']['B']  / $area['total_notas'] * 100);
                        $pctC  = round($area['distribucion']['C']  / $area['total_notas'] * 100);
                    @endphp
                    @if($pctAD > 0)<div class="bg-blue-500 h-full" style="width:{{ $pctAD }}%" title="AD: {{ $pctAD }}%"></div>@endif
                    @if($pctA > 0)<div class="bg-green-500 h-full" style="width:{{ $pctA }}%" title="A: {{ $pctA }}%"></div>@endif
                    @if($pctB > 0)<div class="bg-amber-400 h-full" style="width:{{ $pctB }}%" title="B: {{ $pctB }}%"></div>@endif
                    @if($pctC > 0)<div class="bg-red-400 h-full" style="width:{{ $pctC }}%" title="C: {{ $pctC }}%"></div>@endif
                @endif
            </div>
            <div class="flex gap-4 mt-1">
                <span class="text-xs text-blue-600">AD: {{ $area['distribucion']['AD'] }}</span>
                <span class="text-xs text-green-600">A: {{ $area['distribucion']['A'] }}</span>
                <span class="text-xs text-amber-600">B: {{ $area['distribucion']['B'] }}</span>
                <span class="text-xs text-red-600">C: {{ $area['distribucion']['C'] }}</span>
            </div>
        </div>

        {{-- Competencias --}}
        @if(!empty($area['distribucion_por_competencia']))
        <div class="divide-y divide-gray-50">
            @foreach($area['distribucion_por_competencia'] as $comp)
            <div class="px-4 py-3">
                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-xs text-gray-700">
                        <span class="font-mono font-semibold text-gray-500 mr-1">{{ $comp['code'] }}</span>
                        {{ $comp['name'] }}
                    </p>
                    <span class="text-xs {{ $comp['pct_inicio'] > 50 ? 'text-red-600 font-semibold' : ($comp['pct_inicio'] > 30 ? 'text-amber-600' : 'text-green-600') }} flex-shrink-0 ml-2">
                        {{ $comp['pct_inicio'] > 50 ? '⚠ ' : '' }}{{ $comp['pct_logro'] }}% logro
                    </span>
                </div>
                @if($comp['total'] > 0)
                <div class="flex h-2 rounded-full overflow-hidden gap-0.5">
                    @php
                        $cAD = round($comp['distribucion']['AD'] / $comp['total'] * 100);
                        $cA  = round($comp['distribucion']['A']  / $comp['total'] * 100);
                        $cB  = round($comp['distribucion']['B']  / $comp['total'] * 100);
                        $cC  = round($comp['distribucion']['C']  / $comp['total'] * 100);
                    @endphp
                    @if($cAD > 0)<div class="bg-blue-500 h-full" style="width:{{ $cAD }}%"></div>@endif
                    @if($cA > 0)<div class="bg-green-500 h-full" style="width:{{ $cA }}%"></div>@endif
                    @if($cB > 0)<div class="bg-amber-400 h-full" style="width:{{ $cB }}%"></div>@endif
                    @if($cC > 0)<div class="bg-red-400 h-full" style="width:{{ $cC }}%"></div>@endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach

    {{-- Leyenda --}}
    <div class="flex items-center gap-4 mt-4 px-2">
        <span class="text-xs text-gray-400 font-medium">Leyenda:</span>
        <span class="flex items-center gap-1 text-xs text-gray-500"><span class="w-3 h-3 bg-blue-500 rounded-sm inline-block"></span> AD Destacado</span>
        <span class="flex items-center gap-1 text-xs text-gray-500"><span class="w-3 h-3 bg-green-500 rounded-sm inline-block"></span> A Esperado</span>
        <span class="flex items-center gap-1 text-xs text-gray-500"><span class="w-3 h-3 bg-amber-400 rounded-sm inline-block"></span> B Proceso</span>
        <span class="flex items-center gap-1 text-xs text-gray-500"><span class="w-3 h-3 bg-red-400 rounded-sm inline-block"></span> C Inicio</span>
    </div>
</div>
@endif


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

{{-- Lista de estudiantes en riesgo --}}
@if(!empty($report->at_risk_students['students_list']))
<div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
        Estudiantes identificados en riesgo
        <span class="ml-auto text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-medium">
            {{ count($report->at_risk_students['students_list']) }} estudiantes
        </span>
    </h3>
    <p class="text-xs text-gray-400 mb-4">Estudiantes con nivel de inicio (C) en 2 o más áreas curriculares — requieren atención prioritaria.</p>

    <div class="overflow-hidden border border-gray-100 rounded-xl">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2.5 text-left font-medium text-gray-600">#</th>
                    <th class="px-4 py-2.5 text-left font-medium text-gray-600">Estudiante</th>
                    <th class="px-4 py-2.5 text-center font-medium text-gray-600">Áreas en inicio</th>
                    <th class="px-4 py-2.5 text-left font-medium text-gray-600">Áreas afectadas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($report->at_risk_students['students_list'] as $idx => $student)
                <tr class="{{ $student['total_areas_c'] >= 4 ? 'bg-red-50' : '' }} hover:bg-gray-50 transition-all">
                    <td class="px-4 py-2.5 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                    <td class="px-4 py-2.5 font-medium text-gray-900">{{ $student['nombre'] }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                            {{ $student['total_areas_c'] >= 4 ? 'bg-red-500 text-white' : 'bg-amber-100 text-amber-700' }}">
                            {{ $student['total_areas_c'] }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <div class="flex flex-wrap gap-1">
                            @foreach($student['areas_en_inicio'] as $area)
                            <span class="bg-red-50 text-red-600 border border-red-100 px-1.5 py-0.5 rounded text-xs font-mono">
                                {{ $area }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if(($report->at_risk_students['count'] ?? 0) > count($report->at_risk_students['students_list']))
    <p class="text-xs text-gray-400 mt-3 text-center">
        Mostrando {{ count($report->at_risk_students['students_list']) }} de {{ $report->at_risk_students['count'] }} estudiantes en riesgo.
        Para ver la lista completa sube todos los archivos y genera un análisis institucional.
    </p>
    @endif
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
    <a href="{{ route('analysis.pdf', $report) }}"
        class="bg-red-600 text-white px-6 py-3 rounded-xl text-sm font-medium hover:bg-red-700 transition-all flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Descargar PDF
    </a>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if(!empty($report->summary_data['areas']))
const areasData = @json(array_values($report->summary_data['areas']));

const labels = areasData.map(a => a.nombre);
const dataAD  = areasData.map(a => a.distribucion.AD);
const dataA   = areasData.map(a => a.distribucion.A);
const dataB   = areasData.map(a => a.distribucion.B);
const dataC   = areasData.map(a => a.distribucion.C);

const ctx = document.getElementById('areasChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'AD — Logro destacado',
                data: dataAD,
                backgroundColor: '#3b82f6',
                borderRadius: 4,
            },
            {
                label: 'A — Logro esperado',
                data: dataA,
                backgroundColor: '#22c55e',
                borderRadius: 4,
            },
            {
                label: 'B — En proceso',
                data: dataB,
                backgroundColor: '#f59e0b',
                borderRadius: 4,
            },
            {
                label: 'C — En inicio',
                data: dataC,
                backgroundColor: '#ef4444',
                borderRadius: 4,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                callbacks: {
                    title: (items) => items[0].label,
                    label: (item) => ` ${item.dataset.label}: ${item.raw} estudiantes`,
                }
            }
        },
        scales: {
            x: {
                stacked: false,
                grid: { display: false },
                ticks: {
                    font: { size: 10 },
                    maxRotation: 45,
                    minRotation: 30,
                }
            },
            y: {
                stacked: false,
                grid: { color: '#f1f5f9' },
                ticks: { font: { size: 10 } },
                title: {
                    display: true,
                    text: 'N° de estudiantes',
                    font: { size: 10 },
                    color: '#9ca3af',
                }
            }
        }
    }
});
@endif
</script>

@endsection