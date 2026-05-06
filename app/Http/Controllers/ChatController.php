<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnalysisReport;
use App\Models\ImprovementPlan;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index()
    {
        $reports = AnalysisReport::where('institution_id', auth()->user()->institution_id)
            ->latest()
            ->take(3)
            ->get();

        $plans = ImprovementPlan::where('institution_id', auth()->user()->institution_id)
            ->latest()
            ->take(2)
            ->get();

        return view('chat.index', compact('reports', 'plans'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:1000',
            'history'    => 'nullable|array',
        ]);

        $context = $this->buildContext();
        $response = $this->callClaude($request->message, $request->history ?? [], $context);

        return response()->json([
            'response' => $response ?? 'Lo siento, no pude procesar tu consulta. Intenta de nuevo.',
        ]);
    }

    private function buildContext()
    {
        $institution = auth()->user()->institution;
        $reports = AnalysisReport::where('institution_id', auth()->user()->institution_id)
            ->latest()
            ->take(3)
            ->get();

        $plans = ImprovementPlan::where('institution_id', auth()->user()->institution_id)
            ->latest()
            ->take(2)
            ->get();

        $context = "Eres un asistente educativo especializado en el sistema peruano EBR. ";
        $context .= "Estás ayudando a la institución educativa '{$institution->name}' ubicada en {$institution->district}, {$institution->region}. ";
        $context .= "El usuario es un " . auth()->user()->role . ". ";
        $context .= "Responde siempre en español, de forma clara y práctica. ";
        $context .= "Cuando des recomendaciones considera el contexto rural andino de Huancavelica.\n\n";

        if ($reports->isNotEmpty()) {
            $context .= "ANÁLISIS RECIENTES DISPONIBLES:\n";
            foreach ($reports as $report) {
                $context .= "- Análisis {$report->academic_year} ({$report->type}): ";
                $context .= "{$report->summary_data['total_students']} estudiantes. ";
                $context .= "Áreas críticas: " . collect($report->critical_areas)->pluck('area')->implode(', ') . ". ";
                $context .= "Estudiantes en riesgo: {$report->at_risk_students['count']} ({$report->at_risk_students['percentage']}%).\n";
            }
        }

        if ($plans->isNotEmpty()) {
            $context .= "\nPLANES DE MEJORA ACTIVOS:\n";
            foreach ($plans as $plan) {
                $context .= "- {$plan->title} (Estado: {$plan->status}): ";
                $ejes = collect($plan->axes)->pluck('title')->implode(', ');
                $context .= "Ejes: {$ejes}.\n";
            }
        }

        return $context;
    }

    private function callClaude($message, $history, $context)
    {
        try {
            $messages = [];

            // Agregar historial
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = [
                        'role'    => $msg['role'],
                        'content' => $msg['content'],
                    ];
                }
            }

            // Agregar mensaje actual
            $messages[] = [
                'role'    => 'user',
                'content' => $message,
            ];

            $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5',
                'max_tokens' => 1000,
                'system'     => $context,
                'messages'   => $messages,
            ]);

            return $response->json()['content'][0]['text'] ?? null;

        } catch (\Exception $e) {
            return null;
        }
    }
}