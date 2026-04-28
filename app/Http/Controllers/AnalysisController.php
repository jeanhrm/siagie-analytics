<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\AnalysisReport;
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

        $rawData = json_decode($upload->raw_data, true);

        if (!$rawData) {
            return redirect()->route('uploads.index')
                ->with('error', 'No hay datos procesados para este archivo.');
        }

        // Parsear estructura SIAGIE
        $siagieData = $this->parseSiagie($rawData);

        if (empty($siagieData['areas'])) {
            return redirect()->route('uploads.index')
                ->with('error', 'No se pudieron extraer datos del archivo SIAGIE.');
        }

        // Calcular métricas
        $summaryData = $this->calculateMetrics($siagieData);

        // Enviar a Claude
        $prompt = $this->buildPrompt($upload, $summaryData, $siagieData);
        $aiResponse = $this->callClaude($prompt);

        if (!$aiResponse) {
            return redirect()->route('uploads.index')
                ->with('error', 'Error al conectar con la IA. Verifica tu API Key.');
        }

        // Guardar reporte
        $report = AnalysisReport::create([
            'institution_id'  => auth()->user()->institution_id,
            'upload_id'       => $upload->id,
            'academic_year'   => $upload->academic_year,
            'summary_data'    => $summaryData,
            'ai_analysis'     => $aiResponse['analysis'],
            'critical_areas'  => $aiResponse['critical_areas'],
            'strengths'       => $aiResponse['strengths'],
            'at_risk_students'=> $aiResponse['at_risk'],
            'status'          => 'generated',
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

    private function parseSiagie($rawData)
    {
        // rawData es array de hojas: [nombre_hoja => [[fila1], [fila2], ...]]
        $areas = [];
        $students = [];
        $generalInfo = [];

        foreach ($rawData as $sheetName => $rows) {
            if (!is_array($rows) || count($rows) < 3) continue;

            // Ignorar hojas de metadatos
            if (in_array($sheetName, ['Generalidades', 'Parametros'])) {
                if ($sheetName === 'Generalidades') {
                    $generalInfo = $this->parseGeneralidades($rows);
                }
                continue;
            }

            // Es una hoja de área curricular
            $areaName = $sheetName;
            $header1 = $rows[0] ?? []; // competencias
            $header2 = $rows[1] ?? []; // NL / Conclusión

            // Encontrar columnas NL (nivel de logro)
            $nlColumns = [];
            foreach ($header2 as $colIdx => $cellValue) {
                if ($cellValue === 'NL') {
                    $competencia = $header1[$colIdx] ?? ('C' . $colIdx);
                    $nlColumns[$colIdx] = $competencia;
                }
            }

            if (empty($nlColumns)) continue;

            // Leer estudiantes
            $areaStudents = [];
            for ($i = 2; $i < count($rows); $i++) {
                $row = $rows[$i];
                $nombre = $row[2] ?? null;
                if (!$nombre || !is_string($nombre)) continue;

                $notas = [];
                foreach ($nlColumns as $colIdx => $competencia) {
                    $nota = $row[$colIdx] ?? null;
                    if ($nota && in_array($nota, ['AD', 'A', 'B', 'C'])) {
                        $notas["C{$competencia}"] = $nota;
                    }
                }

                if (!empty($notas)) {
                    $areaStudents[] = [
                        'nombre' => $nombre,
                        'notas'  => $notas,
                    ];
                    $students[$nombre] = $nombre;
                }
            }

            // Calcular distribución de notas del área
            $distribucion = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];
            foreach ($areaStudents as $st) {
                foreach ($st['notas'] as $nota) {
                    if (isset($distribucion[$nota])) {
                        $distribucion[$nota]++;
                    }
                }
            }

            $totalNotas = array_sum($distribucion);
            $areas[$areaName] = [
                'nombre'       => $areaName,
                'estudiantes'  => count($areaStudents),
                'distribucion' => $distribucion,
                'total_notas'  => $totalNotas,
                'pct_logro'    => $totalNotas > 0
                    ? round(($distribucion['AD'] + $distribucion['A']) / $totalNotas * 100, 1)
                    : 0,
                'pct_proceso'  => $totalNotas > 0
                    ? round($distribucion['B'] / $totalNotas * 100, 1)
                    : 0,
                'pct_inicio'   => $totalNotas > 0
                    ? round($distribucion['C'] / $totalNotas * 100, 1)
                    : 0,
            ];
        }

        return [
            'areas'       => $areas,
            'total_students' => count($students),
            'general_info'   => $generalInfo,
        ];
    }

    private function parseGeneralidades($rows)
    {
        $info = [];
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                if ($cell === 'Nombre :' && isset($row[$i + 1])) {
                    $info['nombre_ie'] = $row[$i + 1];
                }
                if ($cell === 'Año académico :' && isset($row[$i + 1])) {
                    $info['año'] = $row[$i + 1];
                }
                if ($cell === 'Período de evaluación :' && isset($row[$i + 1])) {
                    $info['periodo'] = $row[$i + 1];
                }
                if ($cell === 'Grado :' && isset($row[$i + 1])) {
                    $info['grado'] = $row[$i + 1];
                }
            }
        }
        return $info;
    }

    private function calculateMetrics($siagieData)
    {
        $areas = $siagieData['areas'];

        // Ordenar por % de inicio (C) — las más críticas primero
        uasort($areas, fn($a, $b) => $b['pct_inicio'] <=> $a['pct_inicio']);

        $criticalAreas = array_filter($areas, fn($a) => $a['pct_inicio'] > 30);
        $strongAreas   = array_filter($areas, fn($a) => $a['pct_logro'] > 60);

        return [
            'total_students' => $siagieData['total_students'],
            'total_areas'    => count($areas),
            'areas'          => $areas,
            'critical_count' => count($criticalAreas),
            'strong_count'   => count($strongAreas),
            'general_info'   => $siagieData['general_info'],
        ];
    }

    private function buildPrompt($upload, $summaryData, $siagieData)
    {
        $areasResumen = [];
        foreach ($summaryData['areas'] as $nombre => $area) {
            $areasResumen[] = "- {$nombre}: AD={$area['distribucion']['AD']}, A={$area['distribucion']['A']}, B={$area['distribucion']['B']}, C={$area['distribucion']['C']} | Logro={$area['pct_logro']}% | Inicio={$area['pct_inicio']}%";
        }
        $areasTexto = implode("\n", $areasResumen);

        $info = $summaryData['general_info'];
        $ie = $info['nombre_ie'] ?? 'IE desconocida';
        $periodo = $info['periodo'] ?? $upload->academic_year;
        $grado = $info['grado'] ?? '';

        return "Eres un experto en análisis educativo del sistema peruano (EBR). Analiza los datos SIAGIE de esta institución educativa.

INSTITUCIÓN: {$ie}
GRADO: {$grado}
PERÍODO: {$periodo}
TOTAL ESTUDIANTES: {$summaryData['total_students']}
TOTAL ÁREAS CURRICULARES: {$summaryData['total_areas']}

ESCALA DE CALIFICACIÓN:
- AD = Logro destacado
- A = Logro esperado  
- B = En proceso
- C = En inicio (crítico)

RESULTADOS POR ÁREA CURRICULAR:
{$areasTexto}

Analiza estos datos y responde ÚNICAMENTE en formato JSON con esta estructura exacta:
{
    \"analysis\": \"Análisis narrativo completo en 3-4 párrafos sobre el estado educativo, patrones identificados y situación general de la institución\",
    \"critical_areas\": [
        {\"area\": \"nombre del área\", \"description\": \"descripción del problema con datos específicos\", \"severity\": \"alta/media/baja\"}
    ],
    \"strengths\": [
        {\"area\": \"nombre del área o aspecto\", \"description\": \"descripción de la fortaleza con datos\"}
    ],
    \"at_risk\": {
        \"count\": número estimado de estudiantes en inicio,
        \"percentage\": porcentaje estimado,
        \"main_factors\": [\"factor 1\", \"factor 2\", \"factor 3\"]
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

            $text = preg_replace('/```json|```/', '', $text);
            $text = trim($text);

            return json_decode($text, true);

        } catch (\Exception $e) {
            return null;
        }
    }
}