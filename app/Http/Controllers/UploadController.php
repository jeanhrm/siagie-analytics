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

        // Procesar Excel inmediatamente en memoria
        try {
            $data = Excel::toArray([], $file);
            $rows = $data[0] ?? [];
            $totalRows = max(0, count($rows) - 1);

            // Guardar datos procesados en BD
            $upload = Upload::create([
                'institution_id' => auth()->user()->institution_id,
                'filename'       => $filename,
                'original_name'  => $file->getClientOriginalName(),
                'type'           => $request->type,
                'academic_year'  => $request->academic_year,
                'status'         => 'done',
                'total_rows'     => $totalRows,
                'raw_data'       => json_encode($rows),
            ]);

            return redirect()->route('uploads.index')
                ->with('success', "Archivo procesado correctamente. Se encontraron {$totalRows} registros.");

        } catch (\Exception $e) {
            return redirect()->route('uploads.index')
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
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