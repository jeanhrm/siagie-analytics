<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index()
    {
        $uploads = Upload::where('institution_id', auth()->user()->institution_id)
        ->with('analysisReport')
        ->latest()
        ->get();

        return view('uploads.index', compact('uploads'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'excel_file'    => 'required|file|mimes:xlsx,xls|max:10240',
            'type'          => 'required|string',
            'academic_year' => 'required|string',
        ]);

        $file = $request->file('excel_file');
        $filename = time() . '_' . $file->getClientOriginalName();

        try {
            // Leer todas las hojas
            $data = Excel::toArray([], $file);

            // Convertir a array asociativo por nombre de hoja
            $sheetsData = [];
            $sheetNames = [];

            // Obtener nombres de hojas usando PhpSpreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            foreach ($spreadsheet->getSheetNames() as $idx => $name) {
                $sheetsData[$name] = $data[$idx] ?? [];
            }

            $totalRows = 0;
            foreach ($sheetsData as $name => $rows) {
                if (!in_array($name, ['Generalidades', 'Parametros'])) {
                    $totalRows += max(0, count($rows) - 2);
                }
            }

            $upload = Upload::create([
                'institution_id' => auth()->user()->institution_id,
                'filename'       => $filename,
                'original_name'  => $file->getClientOriginalName(),
                'type'           => $request->type,
                'academic_year'  => $request->academic_year,
                'status'         => 'done',
                'total_rows'     => $totalRows,
                'raw_data'       => json_encode($sheetsData),
            ]);

            return redirect()->route('uploads.index')
                ->with('success', "Archivo procesado. {$totalRows} registros en " . count($sheetsData) . " áreas curriculares.");

        } catch (\Exception $e) {
            return redirect()->route('uploads.index')
                ->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }
    



    public function destroy($id)
    {
        $upload = Upload::where('id', $id)
            ->where('institution_id', auth()->user()->institution_id)
            ->firstOrFail();

        Storage::disk('public')->delete('uploads/' . $upload->filename);
        $upload->delete();

        return redirect()->route('uploads.index')
            ->with('success', 'Archivo eliminado correctamente.');
    }
}