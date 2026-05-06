<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\AnalysisReport;
use App\Models\ImprovementPlan;

class DashboardController extends Controller
{
    public function index()
    {
        $institutionId = auth()->user()->institution_id;

        // Métricas principales
        $totalUploads = Upload::where('institution_id', $institutionId)->count();

        // Último análisis disponible
        $latestReport = AnalysisReport::where('institution_id', $institutionId)
            ->latest()
            ->first();

        $totalStudents  = $latestReport?->summary_data['total_students'] ?? null;
        $atRiskCount    = $latestReport?->at_risk_students['count'] ?? null;
        $atRiskPct      = $latestReport?->at_risk_students['percentage'] ?? null;

        // Calcular tasa de aprobación del último análisis
        $approvalRate = null;
        if ($latestReport) {
            $areas = $latestReport->summary_data['areas'] ?? [];
            if (!empty($areas)) {
                $totalLogro = 0;
                $totalNotas = 0;
                foreach ($areas as $area) {
                    $totalLogro += ($area['distribucion']['AD'] ?? 0) + ($area['distribucion']['A'] ?? 0);
                    $totalNotas += $area['total_notas'] ?? 0;
                }
                $approvalRate = $totalNotas > 0 ? round($totalLogro / $totalNotas * 100, 1) : null;
            }
        }

        // Último plan de mejora
        $latestPlan = ImprovementPlan::where('institution_id', $institutionId)
            ->latest()
            ->first();

        // Áreas críticas del último análisis
        $criticalAreas = $latestReport?->critical_areas ?? [];

        // Uploads recientes
        $recentUploads = Upload::where('institution_id', $institutionId)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard', compact(
            'totalUploads',
            'totalStudents',
            'atRiskCount',
            'atRiskPct',
            'approvalRate',
            'latestReport',
            'latestPlan',
            'criticalAreas',
            'recentUploads'
        ));
    }
}