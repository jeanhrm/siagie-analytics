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

        // Guardar archivo
        $path = $file->storeAs('uploads', $filename, 'public');

        // Registrar en BD
        $upload = Upload::create([
            'institution_id' => auth()->user()->institution_id,
            'filename'       => $filename,
            'original_name'  => $file->getClientOriginalName(),
            'type'           => $request->type,
            'academic_year'  => $request->academic_year,
            'status'         => 'processing',
        ]);

        // Procesar Excel
        try {
            $data = Excel::toArray([], storage_path('app/public/uploads/' . $filename));
            $rows = count($data[0] ?? []) - 1; // sin cabecera

            $upload->update([
                'status'     => 'done',
                'total_rows' => max(0, $rows),
            ]);

            return redirect()->route('uploads.index')
                ->with('success', "Archivo procesado correctamente. Se encontraron {$rows} registros.");

        } catch (\Exception $e) {
            $upload->update([
                'status'        => 'error',
                'error_message' => $e->getMessage(),
            ]);

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