<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnalysisReport;
use App\Models\ImprovementPlan;
use Illuminate\Support\Facades\Http;

class ImprovementPlanController extends Controller
{
    public function index()
    {
        $plans = ImprovementPlan::where('institution_id', auth()->user()->institution_id)
            ->with('analysisReport')
            ->latest()
            ->get();

        return view('plans.index', compact('plans'));
    }

    public function generate(AnalysisReport $report)
    {
        if ($report->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $prompt = $this->buildPlanPrompt($report);
        $aiResponse = $this->callClaude($prompt);

        if (!$aiResponse) {
            return redirect()->route('analysis.show', $report)
                ->with('error', 'Error al generar el plan. Verifica tu API Key.');
        }

        $plan = ImprovementPlan::create([
            'institution_id'     => auth()->user()->institution_id,
            'analysis_report_id' => $report->id,
            'title'              => 'Plan de Mejora — ' . $report->academic_year,
            'academic_year'      => $report->academic_year,
            'axes'               => $aiResponse['axes'],
            'ai_narrative'       => $aiResponse['narrative'],
            'status'             => 'draft',
        ]);

        return redirect()->route('plans.show', $plan)
            ->with('success', 'Plan de mejora generado correctamente.');
    }

    public function show(ImprovementPlan $plan)
    {
        if ($plan->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $plan->load('analysisReport');
        return view('plans.show', compact('plan'));
    }

    public function update(Request $request, ImprovementPlan $plan)
    {
        if ($plan->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $plan->update(['status' => $request->status]);

        return back()->with('success', 'Plan actualizado.');
    }

    private function buildPlanPrompt(AnalysisReport $report)
    {
        $criticalAreas = json_encode($report->critical_areas, JSON_UNESCAPED_UNICODE);
        $strengths     = json_encode($report->strengths, JSON_UNESCAPED_UNICODE);
        $atRisk        = json_encode($report->at_risk_students, JSON_UNESCAPED_UNICODE);
        $context       = $report->summary_data['context'] ?? 'rural_secundaria';
        $totalStudents = $report->summary_data['total_students'] ?? 0;

        $contextMap = [
            'rural_secundaria'  => 'escuela rural de nivel secundaria',
            'rural_primaria'    => 'escuela rural de nivel primaria',
            'urbana_secundaria' => 'escuela urbana de nivel secundaria',
            'urbana_primaria'   => 'escuela urbana de nivel primaria',
        ];
        $contextTexto = $contextMap[$context] ?? 'institución educativa';

        return "Eres un especialista en gestión educativa del sistema peruano EBR. Basándote en el análisis de una {$contextTexto} en Huancavelica con {$totalStudents} estudiantes, genera un plan de mejora institucional concreto y contextualizado.

ÁREAS CRÍTICAS IDENTIFICADAS:
{$criticalAreas}

FORTALEZAS IDENTIFICADAS:
{$strengths}

ESTUDIANTES EN RIESGO:
{$atRisk}

ANÁLISIS PREVIO:
{$report->ai_analysis}

Genera un plan de mejora con 3 ejes estratégicos. Cada eje debe tener acciones concretas, responsables y plazos realistas para una {$contextTexto} en zona andina. Considera:
- Recursos limitados típicos de escuelas rurales de Huancavelica
- Estrategias pedagógicas pertinentes al contexto andino
- Involucramiento de la comunidad y familias
- Enfoque en las áreas más críticas primero
- Potenciar las fortalezas identificadas

Responde ÚNICAMENTE en formato JSON con esta estructura exacta:
{
    \"narrative\": \"Introducción narrativa del plan en 2-3 párrafos explicando el enfoque y prioridades\",
    \"axes\": [
        {
            \"number\": 1,
            \"title\": \"título del eje\",
            \"priority\": \"urgente/importante/potenciar\",
            \"color\": \"red/amber/green\",
            \"description\": \"descripción del eje\",
            \"actions\": [
                {
                    \"action\": \"descripción de la acción concreta\",
                    \"responsible\": \"quién es responsable\",
                    \"deadline\": \"plazo (ej: Semana 1-2)\",
                    \"resources\": \"recursos necesarios\",
                    \"done\": false
                }
            ]
        }
    ]
}";
    }

    private function callClaude($prompt)
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 3000,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            $text = $response->json()['content'][0]['text'] ?? null;
            if (!$text) return null;

            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            return json_decode($text, true);

        } catch (\Exception $e) {
            return null;
        }
    }

    public function exportPdf(ImprovementPlan $plan)
    {
        if ($plan->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $plan->load('analysisReport');
        $institution = auth()->user()->institution;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('plans.pdf', compact('plan', 'institution'))
            ->setPaper('a4', 'portrait');

        $filename = 'plan-mejora-' . str_replace(' ', '-', strtolower($institution->name)) . '-' . $plan->academic_year . '.pdf';

        return $pdf->download($filename);
    }

}