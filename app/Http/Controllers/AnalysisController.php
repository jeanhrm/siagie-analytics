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
            ->where('type', 'aula')
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
            'general_info'   => [
            'nombre_ie' => auth()->user()->institution->name,
            'ugel'      => auth()->user()->institution->ugel,
            'region'    => auth()->user()->institution->region,
        ],
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
        $ie   = $summaryData['general_info']['nombre_ie'] ?? 'IE desconocida';
        $ugel = $summaryData['general_info']['ugel'] ?? '';
    
        $contextMap = [
            'rural_secundaria'  => 'escuela rural de nivel secundaria',
            'rural_primaria'    => 'escuela rural de nivel primaria',
            'urbana_secundaria' => 'escuela urbana de nivel secundaria',
            'urbana_primaria'   => 'escuela urbana de nivel primaria',
        ];
        $contextTexto = $contextMap[$context] ?? $context;

        $areasResumen = [];
        foreach ($summaryData['areas'] as $nombre => $area) {
            $areasResumen[] = "\n📚 ÁREA: {$nombre}";
            $areasResumen[] = "  Global → Logro: {$area['pct_logro']}% | Proceso: {$area['pct_proceso']}% | Inicio: {$area['pct_inicio']}%";

            if (!empty($area['distribucion_por_competencia'])) {
                foreach ($area['distribucion_por_competencia'] as $comp) {
                    $areasResumen[] = "  · [{$comp['code']}] {$comp['name']}";
                    $areasResumen[] = "    AD={$comp['distribucion']['AD']} A={$comp['distribucion']['A']} B={$comp['distribucion']['B']} C={$comp['distribucion']['C']} | Logro={$comp['pct_logro']}% Inicio={$comp['pct_inicio']}%";
                }
            }
        }
        $areasTexto = implode("\n", $areasResumen);
        $salonesTexto = implode(', ', $summaryData['salones']);

        return "Eres un experto en análisis educativo del sistema peruano (EBR). Analiza los datos consolidados de la institución educativa '{$ie}' ({$ugel}), una {$contextTexto} ubicada en Huancavelica, Perú.

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
            'at_risk_students' => array_merge(
                $aiResponse['at_risk'] ?? [],
                ['students_list' => array_slice($summaryData['at_risk_students'], 0, 20)]
            ),
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
        $areas = [];
        $students = [];
        $generalInfo = [];

        foreach ($rawData as $sheetName => $rows) {
            if (!is_array($rows) || count($rows) < 3) continue;

            if (in_array($sheetName, ['Generalidades', 'Parametros'])) {
                if ($sheetName === 'Generalidades') {
                    $generalInfo = $this->parseGeneralidades($rows);
                }
                continue;
            }

            $header1 = $rows[0] ?? [];
            $header2 = $rows[1] ?? [];

            // Extraer competencias de la leyenda al final de la hoja
            $competencias = $this->extractCompetencias($rows);

            // Encontrar columnas NL
            $nlColumns = [];
            foreach ($header2 as $colIdx => $cellValue) {
                if ($cellValue === 'NL') {
                    $compCode = $header1[$colIdx] ?? ('C' . $colIdx);
                    $compName = $competencias[$compCode] ?? "Competencia {$compCode}";
                    $nlColumns[$colIdx] = [
                        'code' => $compCode,
                        'name' => $compName,
                    ];
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
                foreach ($nlColumns as $colIdx => $compData) {
                    $nota = $row[$colIdx] ?? null;
                    if ($nota && in_array($nota, ['AD', 'A', 'B', 'C'])) {
                        $notas[$compData['code']] = $nota;
                    }
                }

                if (!empty($notas)) {
                    $areaStudents[] = [
                        'nombre' => $nombre,
                        'notas'  => $notas,
                    ];
                    $students[$nombre] = isset($students[$nombre])
                        ? array_merge($students[$nombre], [$sheetName => $notas])
                        : [$sheetName => $notas];
                }
            }

            // Calcular distribución global del área
            $distribucionTotal = ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0];

            // Calcular distribución por competencia
            $distribucionPorComp = [];
            foreach ($nlColumns as $colIdx => $compData) {
                $distribucionPorComp[$compData['code']] = [
                    'code'         => $compData['code'],
                    'name'         => $compData['name'],
                    'distribucion' => ['AD' => 0, 'A' => 0, 'B' => 0, 'C' => 0],
                    'total'        => 0,
                ];
            }

            foreach ($areaStudents as $st) {
                foreach ($st['notas'] as $compCode => $nota) {
                    if (isset($distribucionTotal[$nota])) {
                        $distribucionTotal[$nota]++;
                    }
                    if (isset($distribucionPorComp[$compCode]['distribucion'][$nota])) {
                        $distribucionPorComp[$compCode]['distribucion'][$nota]++;
                        $distribucionPorComp[$compCode]['total']++;
                    }
                }
            }

            // Calcular porcentajes por competencia
            foreach ($distribucionPorComp as &$comp) {
                $total = $comp['total'];
                $comp['pct_logro']   = $total > 0 ? round(($comp['distribucion']['AD'] + $comp['distribucion']['A']) / $total * 100, 1) : 0;
                $comp['pct_proceso'] = $total > 0 ? round($comp['distribucion']['B'] / $total * 100, 1) : 0;
                $comp['pct_inicio']  = $total > 0 ? round($comp['distribucion']['C'] / $total * 100, 1) : 0;
            }

            $totalNotas = array_sum($distribucionTotal);
            $areas[$sheetName] = [
                'nombre'              => $sheetName,
                'competencias_names'  => $competencias,
                'estudiantes'         => count($areaStudents),
                'distribucion'        => $distribucionTotal,
                'distribucion_por_competencia' => $distribucionPorComp,
                'total_notas'         => $totalNotas,
                'pct_logro'           => $totalNotas > 0 ? round(($distribucionTotal['AD'] + $distribucionTotal['A']) / $totalNotas * 100, 1) : 0,
                'pct_proceso'         => $totalNotas > 0 ? round($distribucionTotal['B'] / $totalNotas * 100, 1) : 0,
                'pct_inicio'          => $totalNotas > 0 ? round($distribucionTotal['C'] / $totalNotas * 100, 1) : 0,
            ];
        }

        // Identificar estudiantes en riesgo (tienen C en al menos 2 áreas)
        $atRiskStudents = [];
        foreach ($students as $nombre => $areasNotas) {
            $areasEnInicio = [];
            foreach ($areasNotas as $area => $notas) {
                $tieneC = in_array('C', array_values($notas));
                if ($tieneC) {
                    $areasEnInicio[] = $area;
                }
            }
            if (count($areasEnInicio) >= 2) {
                $atRiskStudents[] = [
                    'nombre'           => $nombre,
                    'areas_en_inicio'  => $areasEnInicio,
                    'total_areas_c'    => count($areasEnInicio),
                ];
            }
        }

        // Ordenar por más áreas en inicio
        usort($atRiskStudents, fn($a, $b) => $b['total_areas_c'] <=> $a['total_areas_c']);

        return [
            'areas'            => $areas,
            'total_students'   => count($students),
            'general_info'     => $generalInfo,
            'at_risk_students' => $atRiskStudents,
        ];



    }

    private function extractCompetencias($rows)
    {
        $competencias = [];
        foreach ($rows as $row) {
            if (!isset($row[1]) || !is_string($row[1])) continue;
            // Buscar patrón "01 = Nombre de competencia"
            if (preg_match('/^(\d{2})\s*=\s*(.+)$/', trim($row[1]), $matches)) {
                $competencias[$matches[1]] = trim($matches[2]);
            }
        }
        return $competencias;
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

        uasort($areas, fn($a, $b) => $b['pct_inicio'] <=> $a['pct_inicio']);

        $criticalAreas = array_filter($areas, fn($a) => $a['pct_inicio'] > 30);
        $strongAreas   = array_filter($areas, fn($a) => $a['pct_logro'] > 60);

        return [
            'total_students'   => $siagieData['total_students'],
            'total_areas'      => count($areas),
            'areas'            => $areas,
            'critical_count'   => count($criticalAreas),
            'strong_count'     => count($strongAreas),
            'general_info'     => $siagieData['general_info'],
            'at_risk_students' => $siagieData['at_risk_students'] ?? [],
        ];
    }
    

    private function buildPrompt($upload, $summaryData, $siagieData)
    {
        $areasResumen = [];
        foreach ($summaryData['areas'] as $nombre => $area) {
            $areasResumen[] = "\n📚 ÁREA: {$nombre}";
            $areasResumen[] = "  Global → Logro: {$area['pct_logro']}% | Proceso: {$area['pct_proceso']}% | Inicio: {$area['pct_inicio']}%";

            if (!empty($area['distribucion_por_competencia'])) {
                foreach ($area['distribucion_por_competencia'] as $comp) {
                    $areasResumen[] = "  · [{$comp['code']}] {$comp['name']}";
                    $areasResumen[] = "    AD={$comp['distribucion']['AD']} A={$comp['distribucion']['A']} B={$comp['distribucion']['B']} C={$comp['distribucion']['C']} | Logro={$comp['pct_logro']}% Inicio={$comp['pct_inicio']}%";
                }
            }
        }
        $areasTexto = implode("\n", $areasResumen);

        $info = $summaryData['general_info'];
        $ie     = $info['nombre_ie'] ?? 'IE desconocida';
        $periodo = $info['periodo'] ?? $upload->academic_year;
        $grado  = $info['grado'] ?? '';

        // Agregar estudiantes en riesgo al prompt
        $atRiskTexto = '';
        if (!empty($summaryData['at_risk_students'])) {
            $atRiskTexto = "\nESTUDIANTES IDENTIFICADOS EN RIESGO (C en 2+ áreas):\n";
            foreach (array_slice($summaryData['at_risk_students'], 0, 10) as $st) {
                $areas = implode(', ', $st['areas_en_inicio']);
                $atRiskTexto .= "- {$st['nombre']}: en inicio en {$st['total_areas_c']} áreas ({$areas})\n";
            }
            if (count($summaryData['at_risk_students']) > 10) {
                $atRiskTexto .= "... y " . (count($summaryData['at_risk_students']) - 10) . " estudiantes más.\n";
            }
        }

        return "Eres un experto en análisis educativo del sistema peruano (EBR) y en el Currículo Nacional (CNEB). Analiza los datos SIAGIE de esta institución.

    INSTITUCIÓN: {$ie}
    GRADO: {$grado}
    PERÍODO: {$periodo}
    TOTAL ESTUDIANTES: {$summaryData['total_students']}

    ESCALA CNEB:
    - AD = Logro destacado
    - A = Logro esperado
    - B = En proceso
    - C = En inicio (crítico)

    RESULTADOS POR ÁREA Y COMPETENCIA:
    {$areasTexto}

    INSTRUCCIÓN PEDAGÓGICA IMPORTANTE:
    Analiza las competencias de forma HOLÍSTICA por área — no como elementos aislados. Identifica patrones transversales entre competencias del mismo campo. Cuando una competencia está crítica, analiza cómo afecta al desarrollo integral del área.
    {$atRiskTexto}    
    Responde ÚNICAMENTE en formato JSON con esta estructura:
    {
        \"analysis\": \"Análisis narrativo holístico en 3-4 párrafos. Para cada área crítica menciona qué competencias específicas concentran la dificultad y cómo se relacionan entre sí. Usa los nombres reales de las competencias del CNEB.\",
        \"critical_areas\": [
            {
                \"area\": \"nombre del área\",
                \"description\": \"descripción holística del problema mencionando competencias específicas con sus nombres reales\",
                \"severity\": \"alta/media/baja\",
                \"competencias_criticas\": [\"nombre competencia 1\", \"nombre competencia 2\"]
            }
        ],
        \"strengths\": [
            {
                \"area\": \"nombre del área o aspecto\",
                \"description\": \"descripción con datos y competencias destacadas\"
            }
        ],
        \"at_risk\": {
            \"count\": número estimado,
            \"percentage\": porcentaje,
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

    public function exportPdf($reportId)
    {
        $report = AnalysisReport::where('id', $reportId)
            ->where('institution_id', auth()->user()->institution_id)
            ->with('upload')
            ->firstOrFail();

        $institution = auth()->user()->institution;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('analysis.pdf', compact('report', 'institution'))
            ->setPaper('a4', 'portrait');

        $filename = 'analisis-' . str_replace(' ', '-', strtolower($institution->name)) . '-' . $report->academic_year . '.pdf';

        return $pdf->download($filename);
    }



}