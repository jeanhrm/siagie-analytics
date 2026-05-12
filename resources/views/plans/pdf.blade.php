<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Mejora — {{ $institution->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }

        .header { background: #166534; color: white; padding: 24px 32px; margin-bottom: 24px; }
        .header-title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header-sub { font-size: 11px; opacity: 0.8; }
        .header-meta { margin-top: 12px; display: flex; gap: 16px; flex-wrap: wrap; }
        .header-meta span { font-size: 10px; background: rgba(255,255,255,0.15); padding: 3px 10px; border-radius: 4px; }

        .section { margin: 0 32px 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #166534; border-bottom: 2px solid #bbf7d0; padding-bottom: 6px; margin-bottom: 12px; }

        .narrative { background: #f0fdf4; border-left: 3px solid #22c55e; padding: 12px 16px; font-size: 10px; line-height: 1.7; color: #374151; margin-bottom: 8px; }

        .eje { margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .eje-header { padding: 10px 16px; display: flex; align-items: center; gap: 10px; }
        .eje-header.red { background: #fef2f2; border-bottom: 1px solid #fecaca; }
        .eje-header.amber { background: #fffbeb; border-bottom: 1px solid #fde68a; }
        .eje-header.green { background: #f0fdf4; border-bottom: 1px solid #bbf7d0; }
        .eje-num { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: white; flex-shrink: 0; }
        .eje-num.red { background: #ef4444; }
        .eje-num.amber { background: #f59e0b; }
        .eje-num.green { background: #22c55e; }
        .eje-title { font-size: 12px; font-weight: bold; flex: 1; }
        .eje-title.red { color: #991b1b; }
        .eje-title.amber { color: #92400e; }
        .eje-title.green { color: #166534; }
        .eje-desc { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .priority-badge { font-size: 9px; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .priority-urgente { background: #fee2e2; color: #991b1b; }
        .priority-importante { background: #fef3c7; color: #92400e; }
        .priority-potenciar { background: #dcfce7; color: #166534; }

        .actions-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .actions-table th { background: #f8fafc; color: #374151; padding: 6px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-weight: bold; }
        .actions-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .actions-table tr:last-child td { border-bottom: none; }
        .check-done { color: #22c55e; font-weight: bold; }
        .check-pending { color: #d1d5db; }
        .deadline-badge { display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 1px 6px; border-radius: 3px; font-size: 8px; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-active { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #166534; }

        .footer { margin-top: 24px; padding: 12px 32px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 9px; color: #9ca3af; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- ENCABEZADO --}}
<div class="header">
    <div class="header-title">Plan de Mejora Institucional</div>
    <div class="header-sub">{{ $institution->name }} — {{ $institution->ugel }}</div>
    <div class="header-meta">
        <span>Año académico: {{ $plan->academic_year }}</span>
        <span>Estado: {{ ucfirst($plan->status) }}</span>
        <span>Generado: {{ $plan->created_at->format('d/m/Y') }}</span>
        <span>Ejes estratégicos: {{ count($plan->axes) }}</span>
    </div>
</div>

{{-- NARRATIVA --}}
<div class="section">
    <div class="section-title">Introducción y Enfoque del Plan</div>
    <div class="narrative">{{ $plan->ai_narrative }}</div>
</div>

{{-- EJES ESTRATÉGICOS --}}
<div class="section">
    <div class="section-title">Ejes Estratégicos y Acciones</div>

    @foreach($plan->axes as $eje)
    @php
        $color = $eje['color'] ?? 'amber';
        $priority = $eje['priority'] ?? 'importante';
        $priorityMap = [
            'urgente'    => 'URGENTE',
            'importante' => 'IMPORTANTE',
            'potenciar'  => 'POTENCIAR',
        ];
    @endphp

    <div class="eje">
        <div class="eje-header {{ $color }}">
            <div class="eje-num {{ $color }}">{{ $eje['number'] }}</div>
            <div style="flex:1">
                <div class="eje-title {{ $color }}">
                    {{ $eje['title'] }}
                    <span class="priority-badge priority-{{ $priority }}" style="margin-left:8px;">
                        {{ $priorityMap[$priority] ?? strtoupper($priority) }}
                    </span>
                </div>
                <div class="eje-desc">{{ $eje['description'] }}</div>
            </div>
        </div>

        <table class="actions-table">
            <thead>
                <tr>
                    <th style="width:5%"></th>
                    <th style="width:40%">Acción</th>
                    <th style="width:18%">Responsable</th>
                    <th style="width:15%">Plazo</th>
                    <th style="width:22%">Recursos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($eje['actions'] as $action)
                <tr>
                    <td style="text-align:center">
                        @if($action['done'])
                            <span class="check-done">✓</span>
                        @else
                            <span class="check-pending">○</span>
                        @endif
                    </td>
                    <td>{{ $action['action'] }}</td>
                    <td>{{ $action['responsible'] }}</td>
                    <td><span class="deadline-badge">{{ $action['deadline'] }}</span></td>
                    <td style="color:#6b7280;">{{ $action['resources'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

{{-- RESUMEN DE ESTADO --}}
<div class="section">
    <div class="section-title">Estado del Plan</div>
    <table style="width:100%; border-collapse:collapse; font-size:10px;">
        <tr>
            <td style="padding:8px; background:#f8fafc; border:1px solid #e2e8f0; width:30%">
                <strong>Estado actual</strong>
            </td>
            <td style="padding:8px; border:1px solid #e2e8f0;">
                <span class="status-badge status-{{ $plan->status }}">{{ ucfirst($plan->status) }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:8px; background:#f8fafc; border:1px solid #e2e8f0;">
                <strong>Total de acciones</strong>
            </td>
            <td style="padding:8px; border:1px solid #e2e8f0;">
                {{ collect($plan->axes)->sum(fn($e) => count($e['actions'])) }} acciones planificadas
            </td>
        </tr>
        <tr>
            <td style="padding:8px; background:#f8fafc; border:1px solid #e2e8f0;">
                <strong>Acciones completadas</strong>
            </td>
            <td style="padding:8px; border:1px solid #e2e8f0;">
                {{ collect($plan->axes)->sum(fn($e) => collect($e['actions'])->where('done', true)->count()) }} completadas
            </td>
        </tr>
        <tr>
            <td style="padding:8px; background:#f8fafc; border:1px solid #e2e8f0;">
                <strong>Institución</strong>
            </td>
            <td style="padding:8px; border:1px solid #e2e8f0;">
                {{ $institution->name }} — {{ $institution->ugel }}
            </td>
        </tr>
    </table>
</div>

{{-- PIE DE PÁGINA --}}
<div class="footer">
    <span>{{ $institution->name }} — {{ $institution->region }}, Perú</span>
    <span>SIAGIE Analytics — Quipubit / ORBAS Consultores S.A.C.</span>
</div>

</body>
</html>