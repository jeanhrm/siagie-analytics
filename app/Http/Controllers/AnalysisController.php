<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\Student;
use App\Models\Grade;
use App\Models\AnalysisReport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Http;

class AnalysisController extends Controller
{
    public function index()
    {
        $reports = AnalysisReport::where('institution_id', auth()->user()->institution_id)
            ->with('upload')
            ->latest()
            ->get();

        return view('analysis.index', compact('reports'));
    }

    public function generate(Upload $upload)
    {
        if ($upload->institution_id !== auth()->user()->institution_id) {
        abort(403);
        }

        // Leer Excel
        $data = Excel::toArray([], storage_path('app/public/uploads/' . $upload->filename));
        $rows = $data[0] ?? [];

        if (count($rows) < 2) {
            return redirect()->route('uploads.index')
                ->with('error', 'El archivo no tiene datos suficientes.');
        }

        // Procesar datos
        $headers = array_map('trim', $rows[0]);
        $records = [];

        for ($i = 1; $i < count($rows); $i++) {
            $record = [];
            foreach ($headers as $j => $header) {
                $record[$header] = $rows[$i][$j] ?? null;
            }
            $records[] = $record;
        }

        // Calcular métricas básicas
        $totalStudents = count($records);
        $summaryData = [
            'total_students' => $totalStudents,
            'academic_year'  => $upload->academic_year,
            'type'           => $upload->type,
            'headers'        => $headers,
            'sample_records' => array_slice($records, 0, 5),
        ];

        // Calcular promedios por columna numérica
        $numericAverages = [];
        foreach ($headers as $header) {
            $values = array_filter(array_column($records, $header), fn($v) => is_numeric($v));
            if (count($values) > 0) {
                $numericAverages[$header] = round(array_sum($values) / count($values), 2);
            }
        }
        $summaryData['averages'] = $numericAverages;

        // Enviar a Claude
        $prompt = $this->buildPrompt($upload, $summaryData, $records);
        $aiResponse = $this->callClaude($prompt);

        if (!$aiResponse) {
            return redirect()->route('uploads.index')
                ->with('error', 'Error al conectar con la IA. Verifica tu API Key.');
        }

        // Guardar reporte
        $report = AnalysisReport::create([
            'institution_id' => auth()->user()->institution_id,
            'upload_id'      => $upload->id,
            'academic_year'  => $upload->academic_year,
            'summary_data'   => $summaryData,
            'ai_analysis'    => $aiResponse['analysis'],
            'critical_areas' => $aiResponse['critical_areas'],
            'strengths'      => $aiResponse['strengths'],
            'at_risk_students' => $aiResponse['at_risk'],
            'status'         => 'generated',
        ]);

        return redirect()->route('analysis.show', $report)
            ->with('success', 'Análisis generado correctamente.');
    }

    public function show($reportId)
    {
        $report = AnalysisReport::where('id', $reportId)
            ->where('institution_id', auth()->user()->institution_id)
            ->with('upload')
            ->firstOrFail();

        return view('analysis.show', compact('report'));
    }

    private function buildPrompt($upload, $summaryData, $records)
    {
        $sample = json_encode(array_slice($records, 0, 10), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $averages = json_encode($summaryData['averages'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "Eres un experto en análisis educativo del sistema peruano. Analiza los siguientes datos del SIAGIE de una institución educativa.

TIPO DE ARCHIVO: {$upload->type}
AÑO ACADÉMICO: {$upload->academic_year}
TOTAL DE REGISTROS: {$summaryData['total_students']}
COLUMNAS DEL ARCHIVO: " . implode(', ', $summaryData['headers']) . "

PROMEDIOS POR ÁREA/COLUMNA NUMÉRICA:
{$averages}

MUESTRA DE DATOS (primeros 10 registros):
{$sample}

Basándote en estos datos, proporciona un análisis educativo completo. Responde ÚNICAMENTE en formato JSON con esta estructura exacta:
{
    \"analysis\": \"Análisis narrativo completo en 3-4 párrafos sobre el estado educativo de la institución, identificando patrones, tendencias y situación general\",
    \"critical_areas\": [
        {\"area\": \"nombre del área o aspecto crítico\", \"description\": \"descripción del problema\", \"severity\": \"alta/media/baja\"}
    ],
    \"strengths\": [
        {\"area\": \"nombre de la fortaleza\", \"description\": \"descripción de la fortaleza\"}
    ],
    \"at_risk\": {
        \"count\": número estimado de estudiantes en riesgo,
        \"percentage\": porcentaje estimado,
        \"main_factors\": [\"factor 1\", \"factor 2\"]
    }
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
                'max_tokens' => 2000,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            $text = $response->json()['content'][0]['text'] ?? null;

            if (!$text) return null;

            // Limpiar JSON
            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            return json_decode($text, true);

        } catch (\Exception $e) {
            return null;
        }
    }
}