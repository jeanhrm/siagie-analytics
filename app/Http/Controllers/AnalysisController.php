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

    public function institutional()
    {
        // Solo directores
        if (!auth()->user()->isDirector()) {
            abort(403);
        }

        $uploads = \App\Models\Upload::where('institution_id', auth()->user()->institution_id)
            ->where('status', 'done')
            ->latest()
            ->get();

        $reports = AnalysisReport::where('institution_id', auth()->user()->institution_id)
            ->where('type', 'institutional')
            ->with('upload')
            ->latest()
            ->get();

        return view('analysis.institutional', compact('uploads', 'reports'));
    }

    public function generateInstitutional(Request $request)
{
    if (!auth()->user()->isDirector()) {
        abort(403);
    }

    $request->validate([
        'upload_ids'   => 'required|array|min:1',
        'academic_year'=> 'required|string',
        'context'      => 'required|string',
    ]);

    // Cargar todos los uploads seleccionados
    $uploads = \App\Models\Upload::whereIn('id', $request->upload_ids)
        ->where('institution_id', auth()->user()->institution_id)
        ->where('status', 'done')
        ->get();

    if ($uploads->isEmpty()) {
        return redirect()->route('analysis.institutional')
            ->with('error', 'No se encontraron archivos válidos.');
    }

    // Consolidar datos de todos los salones
    $allAreas = [];
    $totalStudents = 0;
    $salones = [];

    foreach ($uploads as $upload) {
        $rawData = json_decode($upload->raw_data, true);
        if (!$rawData) continue;

        $siagieData = $this->parseSiagie($rawData);
        $totalStudents += $siagieData['total_students'];
        $salones[] = $upload->original_name;

        // Consolidar áreas
        foreach ($siagieData['areas'] as $areaName => $areaData) {
            if (!isset($allAreas[$areaName])) {
                $allAreas[$areaName] = [
                    'nombre'       => $areaName,
                    'estudiantes'  => 0,
                    'distribucion' => ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0],
                    'total_notas'  => 0,
                ];
            }
            $allAreas[$areaName]['estudiantes']  += $areaData['estudiantes'];
            $allAreas[$areaName]['total_notas']  += $areaData['total_notas'];
            foreach (['AD', 'A', 'B', 'C'] as $nivel) {
                $allAreas[$areaName]['distribucion'][$nivel] += $areaData['distribucion'][$nivel];
            }
        }
    }

    // Calcular porcentajes consolidados
    foreach ($allAreas as &$area) {
        $total = $area['total_notas'];
        $area['pct_logro']   = $total > 0 ? round(($area['distribucion']['AD'] + $area['distribucion']['A']) / $total * 100, 1) : 0;
        $area['pct_proceso'] = $total > 0 ? round($area['distribucion']['B'] / $total * 100, 1) : 0;
        $area['pct_inicio']  = $total > 0 ? round($area['distribucion']['C'] / $total * 100, 1) : 0;
    }

    // Ordenar por más críticas
    uasort($allAreas, fn($a, $b) => $b['pct_inicio'] <=> $a['pct_inicio']);

    $summaryData = [
        'total_students' => $totalStudents,
        'total_areas'    => count($allAreas),
        'areas'          => $allAreas,
        'salones'        => $salones,
        'context'        => $request->context,
        'critical_count' => count(array_filter($allAreas, fn($a) => $a['pct_inicio'] > 30)),
        'strong_count'   => count(array_filter($allAreas, fn($a) => $a['pct_logro'] > 60)),
        'general_info'   => [],
    ];

    // Construir prompt institucional
    $prompt = $this->buildInstitutionalPrompt($summaryData, $request->context, $request->academic_year);
    $aiResponse = $this->callClaude($prompt);

    if (!$aiResponse) {
        return redirect()->route('analysis.institutional')
            ->with('error', 'Error al conectar con la IA. Verifica tu API Key.');
    }

    $report = AnalysisReport::create([
        'institution_id'   => auth()->user()->institution_id,
        'upload_id'        => $uploads->first()->id,
        'academic_year'    => $request->academic_year,
        'summary_data'     => $summaryData,
        'ai_analysis'      => $aiResponse['analysis'],
        'critical_areas'   => $aiResponse['critical_areas'],
        'strengths'        => $aiResponse['strengths'],
        'at_risk_students' => $aiResponse['at_risk'],
        'status'           => 'generated',
        'type'             => 'institutional',
    ]);

    return redirect()->route('analysis.show', $report)
        ->with('success', 'Análisis institucional generado correctamente.');
}

    private function buildInstitutionalPrompt($summaryData, $context, $academicYear)
    {
        $contextMap = [
            'rural_secundaria'  => 'escuela rural de nivel secundaria',
            'rural_primaria'    => 'escuela rural de nivel primaria',
            'urbana_secundaria' => 'escuela urbana de nivel secundaria',
            'urbana_primaria'   => 'escuela urbana de nivel primaria',
        ];
        $contextTexto = $contextMap[$context] ?? $context;

        $areasResumen = [];
        foreach ($summaryData['areas'] as $nombre => $area) {
            $areasResumen[] = "- {$nombre}: AD={$area['distribucion']['AD']}, A={$area['distribucion']['A']}, B={$area['distribucion']['B']}, C={$area['distribucion']['C']} | Logro={$area['pct_logro']}% | Inicio={$area['pct_inicio']}%";
        }
        $areasTexto = implode("\n", $areasResumen);
        $salonesTexto = implode(', ', $summaryData['salones']);

        return "Eres un experto en análisis educativo del sistema peruano (EBR). Analiza los datos consolidados de una {$contextTexto} ubicada en Huancavelica, Perú.

    AÑO ACADÉMICO: {$academicYear}
    CONTEXTO: {$contextTexto}
    TOTAL ESTUDIANTES: {$summaryData['total_students']}
    SALONES ANALIZADOS: {$salonesTexto}
    TOTAL ÁREAS CURRICULARES: {$summaryData['total_areas']}

    ESCALA DE CALIFICACIÓN CNEB:
    - AD = Logro destacado
    - A = Logro esperado
    - B = En proceso
    - C = En inicio (crítico)

    RESULTADOS CONSOLIDADOS POR ÁREA CURRICULAR:
    {$areasTexto}

    Considera el contexto rural andino de Huancavelica al formular recomendaciones — limitaciones de conectividad, contexto socioeconómico, lengua materna quechua, calendario agrofestivo y recursos disponibles.

    Responde ÚNICAMENTE en formato JSON con esta estructura exacta:
    {
        \"analysis\": \"Análisis narrativo institucional completo en 4-5 párrafos considerando el contexto rural/urbano de la institución\",
        \"critical_areas\": [
            {\"area\": \"nombre del área\", \"description\": \"descripción con datos específicos y recomendación contextualizada\", \"severity\": \"alta/media/baja\"}
        ],
        \"strengths\": [
            {\"area\": \"nombre del área o aspecto\", \"description\": \"descripción de la fortaleza con datos\"}
        ],
        \"at_risk\": {
            \"count\": número estimado de estudiantes en inicio,
            \"percentage\": porcentaje estimado,
            \"main_factors\": [\"factor contextualizado 1\", \"factor 2\", \"factor 3\"]
        }
    }";
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
            'type'            => 'aula',
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