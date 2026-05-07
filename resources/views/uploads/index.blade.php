@extends('layouts.app')

@section('title', 'Cargar Excel')
@section('subtitle', 'Sube los archivos exportados del SIAGIE')

@section('content')

{{-- Zona de carga --}}
<div class="bg-white rounded-2xl border border-gray-100 p-8 mb-6">
    <form action="{{ route('uploads.store') }}" method="POST" enctype="multipart/form-data" id="upload-form">
        @csrf

        <div class="border-2 border-dashed border-blue-200 rounded-2xl p-12 text-center hover:border-blue-400 transition-all cursor-pointer" id="drop-zone">
            <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <p class="text-base font-semibold text-gray-900 mb-1">Arrastra tu archivo Excel aquí</p>
            <p class="text-sm text-gray-400 mb-5">o haz clic para seleccionar</p>
            <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" class="hidden">
            <button type="button" onclick="document.getElementById('excel_file').click()"
                class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all">
                Seleccionar archivo
            </button>
            <div class="flex items-center justify-center gap-3 mt-5 flex-wrap">
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-mono">ACTAS_CONSOLIDADAS</span>
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-mono">NOTAS_PARCIALES</span>
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-mono">ASISTENCIA</span>
                <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-mono">MATRICULAS</span>
            </div>
        </div>

        {{-- Archivo seleccionado --}}
        <div id="file-preview" class="hidden mt-4 bg-gray-50 rounded-xl p-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900" id="file-name">archivo.xlsx</p>
                <p class="text-xs text-gray-400" id="file-size">0 KB</p>
            </div>
            <button type="button" onclick="clearFile()" class="text-gray-400 hover:text-red-500 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Opciones adicionales --}}
        <div class="grid grid-cols-2 gap-4 mt-5">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Tipo de archivo</label>
                <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="actas">Actas Consolidadas</option>
                    <option value="asistencia">Registro de Asistencia</option>
                    <option value="matriculas">Nómina de Matrícula</option>
                    <option value="notas">Notas Parciales</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-2">Año académico</label>
                <select name="academic_year" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="2026">2026</option>
                    <option value="2025">2025</option>
                    <option value="2024">2024</option>
                </select>
            </div>
        </div>

        <button type="submit" id="submit-btn"
            class="w-full mt-5 bg-blue-600 text-white py-3 rounded-xl text-sm font-medium hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Subir y procesar archivo
        </button>
    </form>
</div>

{{-- Lista de archivos subidos --}}
<div class="bg-white rounded-2xl border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900">Archivos cargados</h3>
        <span class="text-xs text-gray-400">{{ $uploads->count() }} archivo(s)</span>
    </div>

    @if($uploads->isEmpty())
    <div class="p-12 text-center">
        <p class="text-sm text-gray-400">Aún no has subido ningún archivo.</p>
    </div>
    @else
    <div class="divide-y divide-gray-50">
        @foreach($uploads as $upload)
        <div class="px-6 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-green-700 font-mono">XLS</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ $upload->original_name }}</p>
                <p class="text-xs text-gray-400">{{ ucfirst($upload->type) }} · {{ $upload->academic_year }} · {{ $upload->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($upload->status === 'done')
                <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">Procesado</span>
                @if($upload->analysisReport)
                    <a href="{{ route('analysis.show', $upload->analysisReport) }}"
                        class="text-xs bg-purple-100 text-purple-700 px-3 py-1.5 rounded-lg font-medium hover:bg-purple-200 transition-all">
                        Ver análisis
                    </a>
                @else
                    <form action="{{ route('analysis.generate', $upload) }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg font-medium hover:bg-blue-700 transition-all">
                            Analizar con IA
                        </button>
                    </form>
                @endif
                @elseif($upload->status === 'processing')
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-medium">Procesando...</span>
                @elseif($upload->status === 'error')
                    <span class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full font-medium">Error</span>
                @else
                    <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full font-medium">Pendiente</span>
                @endif

                <form action="{{ route('uploads.destroy', $upload) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-300 hover:text-red-500 transition-all"
                        onclick="return confirm('¿Eliminar este archivo?')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<script>
document.getElementById('excel_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('file-preview').classList.remove('hidden');
        document.getElementById('drop-zone').classList.add('border-green-400', 'bg-green-50');
    }
});

function clearFile() {
    document.getElementById('excel_file').value = '';
    document.getElementById('file-preview').classList.add('hidden');
    document.getElementById('drop-zone').classList.remove('border-green-400', 'bg-green-50');
}
</script>

@endsection