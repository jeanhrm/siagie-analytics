<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Análisis — {{ $institution->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }

        .header { background: #1d4ed8; color: white; padding: 24px 32px; margin-bottom: 24px; }
        .header-title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header-sub { font-size: 11px; opacity: 0.8; }
        .header-meta { margin-top: 12px; display: flex; gap: 24px; }
        .header-meta span { font-size: 10px; background: rgba(255,255,255,0.15); padding: 3px 10px; border-radius: 4px; }

        .section { margin: 0 32px 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1d4ed8; border-bottom: 2px solid #dbeafe; padding-bottom: 6px; margin-bottom: 12px; }

        .narrative { background: #f8fafc; border-left: 3px solid #6366f1; padding: 12px 16px; font-size: 10px; line-height: 1.7; color: #374151; margin-bottom: 8px; }

        .metrics-grid { display: flex; gap: 12px; margin-bottom: 4px; }
        .metric-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; text-align: center; }
        .metric-value { font-size: 22px; font-weight: bold; color: #1d4ed8; }
        .metric-value.red { color: #dc2626; }
        .metric-value.green { color: #16a34a; }
        .metric-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }

        .areas-grid { display: flex; gap: 12px; }
        .areas-col { flex: 1; }

        .area-card { border-radius: 6px; padding: 10px 12px; margin-bottom: 8px; }
        .area-card.critical { background: #fef2f2; border: 1px solid #fecaca; }
        .area-card.strength { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .area-card-title { font-weight: bold; font-size: 10px; margin-bottom: 3px; }
        .area-card.critical .area-card-title { color: #991b1b; }
        .area-card.strength .area-card-title { color: #166534; }
        .area-card-desc { font-size: 9px; line-height: 1.5; }
        .area-card.critical .area-card-desc { color: #b91c1c; }
        .area-card.strength .area-card-desc { color: #15803d; }
        .severity-badge { display: inline-block; font-size: 8px; padding: 1px 6px; border-radius: 3px; font-weight: bold; margin-left: 6px; }
        .severity-alta { background: #fee2e2; color: #991b1b; }
        .severity-media { background: #fef3c7; color: #92400e; }

        .comp-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        .comp-table th { background: #1d4ed8; color: white; padding: 5px 8px; text-align: left; font-size: 9px; }
        .comp-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
        .comp-table tr:nth-child(even) td { background: #f8fafc; }
        .area-header { background: #eff6ff; font-weight: bold; color: #1e40af; }

        .bar-container { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; display: flex; }
        .bar-ad { background: #3b82f6; height: 100%; }
        .bar-a { background: #22c55e; height: 100%; }
        .bar-b { background: #f59e0b; height: 100%; }
        .bar-c { background: #ef4444; height: 100%; }

        .risk-factors { display: flex; flex-wrap: wrap; gap: 6px; }
        .risk-tag { background: #fff7ed; border: 1px solid #fed7aa; color: #c2410c; font-size: 9px; padding: 3px 8px; border-radius: 4px; }

        .footer { margin-top: 24px; padding: 12px 32px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 9px; color: #9ca3af; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-title">Reporte de Análisis Educativo</div>
    <div class="header-sub">{{ $institution->name }} — {{ $institution->ugel }}</div>
    <div class="header-meta">
        <span>Año académico: {{ $report->academic_year }}</span>
        <span>Tipo: {{ $report->type === 'institutional' ? 'Institucional' : 'Aula' }}</span>
        <span>Generado: {{ $report->created_at->format('d/m/Y') }}</span>
        <span>Total estudiantes: {{ $report->summary_data['total_students'] }}</span>
    </div>
</div>

{{-- MÉTRICAS --}}
<div class="section">
    <div class="section-title">Resumen General</div>
    <div class="metrics-grid">
        <div class="metric-box">
            <div class="metric-value">{{ $report->summary_data['total_students'] }}</div>
            <div class="metric-label">Estudiantes</div>
        </div>
        <div class="metric-box">
            <div class="metric-value red">{{ $report->at_risk_students['count'] ?? '—' }}</div>
            <div class="metric-label">En riesgo (C)</div>
        </div>
        <div class="metric-box">
            <div class="metric-value">{{ $report->at_risk_students['percentage'] ?? '—' }}%</div>
            <div class="metric-label">% en inicio</div>
        </div>
        <div class="metric-box">
            <div class="metric-value green">{{ $report->summary_data['total_areas'] }}</div>
            <div class="metric-label">Áreas analizadas</div>
        </div>
    </div>
</div>

{{-- DIAGNÓSTICO --}}
<div class="section">
    <div class="section-title">Diagnóstico General — Análisis Claude AI</div>
    <div class="narrative">{{ $report->ai_analysis }}</div>
</div>

{{-- ÁREAS CRÍTICAS Y FORTALEZAS --}}
<div class="section">
    <div class="section-title">Áreas Críticas y Fortalezas</div>
    <div class="areas-grid">
        <div class="areas-col">
            <div style="font-size:10px; font-weight:bold; color:#dc2626; margin-bottom:8px;">⚠ Áreas Críticas</div>
            @foreach($report->critical_areas ?? [] as $area)
            <div class="area-card critical">
                <div class="area-card-title">
                    {{ $area['area'] }}
                    <span class="severity-badge severity-{{ $area['severity'] }}">{{ strtoupper($area['severity']) }}</span>
                </div>
                <div class="area-card-desc">{{ $area['description'] }}</div>
                @if(!empty($area['competencias_criticas']))
                <div style="margin-top:4px; font-size:8px; color:#b91c1c;">
                    Competencias: {{ implode(' · ', $area['competencias_criticas']) }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="areas-col">
            <div style="font-size:10px; font-weight:bold; color:#16a34a; margin-bottom:8px;">✓ Fortalezas</div>
            @foreach($report->strengths ?? [] as $strength)
            <div class="area-card strength">
                <div class="area-card-title">{{ $strength['area'] }}</div>
                <div class="area-card-desc">{{ $strength['description'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- FACTORES DE RIESGO --}}
@if(!empty($report->at_risk_students['main_factors']))
<div class="section">
    <div class="section-title">Factores de Riesgo Principales</div>
    <div class="risk-factors">
        @foreach($report->at_risk_students['main_factors'] as $factor)
        <span class="risk-tag">{{ $factor }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- RESULTADOS POR ÁREA Y COMPETENCIA --}}
<div class="page-break"></div>
<div class="section" style="margin-top:24px;">
    <div class="section-title">Resultados por Área y Competencia</div>

    @foreach($report->summary_data['areas'] ?? [] as $areaName => $area)
    <table class="comp-table">
        <thead>
            <tr>
                <th colspan="6" style="background:#1e40af;">{{ $areaName }} — Logro: {{ $area['pct_logro'] }}% | Proceso: {{ $area['pct_proceso'] }}% | Inicio: {{ $area['pct_inicio'] }}%</th>
            </tr>
            <tr>
                <th style="width:35%">Competencia</th>
                <th style="width:6%; text-align:center;">AD</th>
                <th style="width:6%; text-align:center;">A</th>
                <th style="width:6%; text-align:center;">B</th>
                <th style="width:6%; text-align:center;">C</th>
                <th style="width:41%">Distribución</th>
            </tr>
        </thead>
        <tbody>
            @foreach($area['distribucion_por_competencia'] ?? [] as $comp)
            <tr>
                <td><strong>{{ $comp['code'] }}</strong> {{ Str::limit($comp['name'], 50) }}</td>
                <td style="text-align:center; color:#3b82f6; font-weight:bold;">{{ $comp['distribucion']['AD'] }}</td>
                <td style="text-align:center; color:#22c55e; font-weight:bold;">{{ $comp['distribucion']['A'] }}</td>
                <td style="text-align:center; color:#f59e0b; font-weight:bold;">{{ $comp['distribucion']['B'] }}</td>
                <td style="text-align:center; color:#ef4444; font-weight:bold;">{{ $comp['distribucion']['C'] }}</td>
                <td>
                    @if($comp['total'] > 0)
                    <div class="bar-container">
                        @php
                            $bAD = round($comp['distribucion']['AD'] / $comp['total'] * 100);
                            $bA  = round($comp['distribucion']['A']  / $comp['total'] * 100);
                            $bB  = round($comp['distribucion']['B']  / $comp['total'] * 100);
                            $bC  = round($comp['distribucion']['C']  / $comp['total'] * 100);
                        @endphp
                        @if($bAD > 0)<div class="bar-ad" style="width:{{ $bAD }}%"></div>@endif
                        @if($bA > 0)<div class="bar-a" style="width:{{ $bA }}%"></div>@endif
                        @if($bB > 0)<div class="bar-b" style="width:{{ $bB }}%"></div>@endif
                        @if($bC > 0)<div class="bar-c" style="width:{{ $bC }}%"></div>@endif
                    </div>
                    <div style="font-size:8px; color:#6b7280; margin-top:2px;">
                        Logro: {{ $comp['pct_logro'] }}% | Inicio: {{ $comp['pct_inicio'] }}%
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
</div>

{{-- PIE DE PÁGINA --}}
<div class="footer">
    <span>{{ $institution->name }} — {{ $institution->ugel }} — Huancavelica, Perú</span>
    <span>Generado por SIAGIE Analytics — Quipubit / ORBAS Consultores</span>
</div>

</body>
</html>