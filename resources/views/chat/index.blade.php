@extends('layouts.app')
@section('title', 'Asistente IA')
@section('subtitle', 'Consulta sobre los datos de tu institución')


@php
function formatMessagePHP($text) {
    $text = e($text);
    $text = preg_replace('/^### (.+)$/m', '<p class="font-semibold text-gray-900 mt-2 mb-1">$1</p>', $text);
    $text = preg_replace('/^## (.+)$/m', '<p class="font-bold text-gray-900 mt-2 mb-1">$1</p>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/^- (.+)$/m', '<li class="ml-4 list-disc">$1</li>', $text);
    $text = preg_replace('/\n\n/', '<br><br>', $text);
    $text = preg_replace('/\n/', '<br>', $text);
    return $text;
}
@endphp

@section('content')

<div class="grid grid-cols-3 gap-6">

    {{-- Chat principal --}}
    <div class="col-span-2">
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden flex flex-col" style="height: 600px;">

            {{-- Header --}}
            <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
                <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">Asistente SIAGIE</p>
                    <p class="text-xs text-gray-400">Powered by Claude — contexto cargado de tu institución</p>
                </div>
                <div class="ml-auto flex items-center gap-1.5">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-xs text-gray-400">En línea</span>
                </div>
                <form action="{{ route('chat.clear') }}" method="POST" class="ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('¿Limpiar historial?')"
                        class="text-xs text-gray-400 hover:text-red-500 transition-all">
                        Limpiar historial
                    </button>
                </form>
            </div>

            {{-- Mensajes --}}
            <div class="flex-1 overflow-y-auto p-5 space-y-4" id="chat-messages">
                @if($messages->isEmpty())
                <div class="flex gap-3">
                    <div class="w-7 h-7 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <span class="text-xs font-bold text-purple-600">IA</span>
                    </div>
                    <div class="bg-gray-50 rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg">
                        <p class="text-sm text-gray-700 leading-relaxed">
                            Hola, soy tu asistente educativo. Tengo acceso al contexto de
                            <strong>{{ auth()->user()->institution->name }}</strong>
                            y sus análisis recientes. ¿En qué te puedo ayudar?
                        </p>
                    </div>
                </div>
                @else
                @foreach($messages as $msg)
                <div class="flex gap-3 {{ $msg->role === 'user' ? 'flex-row-reverse' : '' }}">
                    <div class="w-7 h-7 {{ $msg->role === 'user' ? 'bg-blue-500' : 'bg-purple-100' }} rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <span class="text-xs font-bold {{ $msg->role === 'user' ? 'text-white' : 'text-purple-600' }}">
                            {{ $msg->role === 'user' ? 'TU' : 'IA' }}
                        </span>
                    </div>
                    <div class="{{ $msg->role === 'user' ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm' : 'bg-gray-50 text-gray-700 rounded-2xl rounded-tl-sm' }} px-4 py-3 max-w-lg">
                        <div class="text-sm leading-relaxed">{!! $msg->role === 'assistant' ? formatMessagePHP($msg->content) : e($msg->content) !!}</div>
                    </div>
                </div>
                @endforeach
                @endif
            </div>

            {{-- Sugerencias rápidas --}}
            <div class="px-5 py-3 border-t border-gray-50" id="suggestions">
                <div class="flex gap-2 flex-wrap">
                    <button onclick="sendSuggestion(this)" class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full hover:bg-purple-100 hover:text-purple-700 transition-all">
                        ¿Qué áreas son más críticas?
                    </button>
                    <button onclick="sendSuggestion(this)" class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full hover:bg-purple-100 hover:text-purple-700 transition-all">
                        ¿Cuántos estudiantes están en riesgo?
                    </button>
                    <button onclick="sendSuggestion(this)" class="text-xs bg-gray-100 text-gray-600 px-3 py-1.5 rounded-full hover:bg-purple-100 hover:text-purple-700 transition-all">
                        ¿Qué estrategias recomiendas?
                    </button>
                </div>
            </div>

            {{-- Input --}}
            <div class="px-5 py-4 border-t border-gray-100">
                <div class="flex gap-3">
                    <input type="text" id="chat-input" placeholder="Escribe tu consulta..."
                        class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        onkeypress="handleKeyPress(event)">
                    <button onclick="sendMessage()"
                        class="bg-purple-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-purple-700 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Enviar
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- Panel lateral --}}
    <div class="space-y-4">

        {{-- Contexto disponible --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Contexto cargado</h3>
            @if($reports->isNotEmpty())
                @foreach($reports as $report)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 bg-purple-500 rounded-full flex-shrink-0"></div>
                    <p class="text-xs text-gray-600">Análisis {{ $report->academic_year }} — {{ $report->type }}</p>
                </div>
                @endforeach
            @else
                <p class="text-xs text-gray-400">Sin análisis disponibles aún.</p>
            @endif

            @if($plans->isNotEmpty())
                @foreach($plans as $plan)
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></div>
                    <p class="text-xs text-gray-600">{{ $plan->title }}</p>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Preguntas frecuentes --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Preguntas frecuentes</h3>
            <div class="space-y-2">
                <button onclick="sendSuggestion(this)" class="w-full text-left text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-all">
                    ¿Qué metodología usar para mejorar matemática?
                </button>
                <button onclick="sendSuggestion(this)" class="w-full text-left text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-all">
                    ¿Cómo involucrar a los padres de familia?
                </button>
                <button onclick="sendSuggestion(this)" class="w-full text-left text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-all">
                    Dame estrategias para estudiantes en inicio
                </button>
                <button onclick="sendSuggestion(this)" class="w-full text-left text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-all">
                    ¿Qué dice el CNEB sobre evaluación formativa?
                </button>
                <button onclick="sendSuggestion(this)" class="w-full text-left text-xs text-gray-600 hover:text-purple-700 hover:bg-purple-50 px-3 py-2 rounded-lg transition-all">
                    Genera una actividad para reforzar comunicación
                </button>
            </div>
        </div>

    </div>
</div>



<script>
    
let chatHistory = [];

function handleKeyPress(e) {
    if (e.key === 'Enter') sendMessage();
}

function sendSuggestion(btn) {
    document.getElementById('chat-input').value = btn.textContent.trim();
    sendMessage();
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    // Ocultar sugerencias después del primer mensaje
    document.getElementById('suggestions').style.display = 'none';

    appendMessage('user', message);
    input.value = '';

    // Typing indicator
    const typingId = appendTyping();

    fetch('{{ route("chat.send") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ message }),
    })
    .then(r => r.json())
    .then(data => {
        removeTyping(typingId);
        appendMessage('assistant', data.response);
    })
    .catch(() => {
        removeTyping(typingId);
        appendMessage('assistant', 'Ocurrió un error. Por favor intenta de nuevo.');
    });
}

function formatMessage(text) {
    return text
        // Headers
        .replace(/^### (.+)$/gm, '<p class="font-semibold text-gray-900 mt-3 mb-1">$1</p>')
        .replace(/^## (.+)$/gm, '<p class="font-bold text-gray-900 mt-3 mb-1">$1</p>')
        .replace(/^# (.+)$/gm, '<p class="font-bold text-gray-900 text-base mt-3 mb-1">$1</p>')
        // Bold
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        // Italic
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        // Listas
        .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')
        .replace(/^(\d+)\. (.+)$/gm, '<li class="ml-4 list-decimal">$2</li>')
        // Saltos de línea
        .replace(/\n\n/g, '<br><br>')
        .replace(/\n/g, '<br>');
}


function appendMessage(role, content) {
    const container = document.getElementById('chat-messages');
    const isUser = role === 'user';

    const div = document.createElement('div');
    div.className = `flex gap-3 ${isUser ? 'flex-row-reverse' : ''}`;
    div.innerHTML = `
        <div class="w-7 h-7 ${isUser ? 'bg-blue-500' : 'bg-purple-100'} rounded-full flex items-center justify-center flex-shrink-0 mt-1">
            <span class="text-xs font-bold ${isUser ? 'text-white' : 'text-purple-600'}">${isUser ? 'TU' : 'IA'}</span>
        </div>
        <div class="${isUser ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm' : 'bg-gray-50 text-gray-700 rounded-2xl rounded-tl-sm'} px-4 py-3 max-w-lg">
            <div class="text-sm leading-relaxed prose-sm">${formatMessage(content)}</div>
        </div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function appendTyping() {
    const container = document.getElementById('chat-messages');
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'flex gap-3';
    div.innerHTML = `
        <div class="w-7 h-7 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
            <span class="text-xs font-bold text-purple-600">IA</span>
        </div>
        <div class="bg-gray-50 rounded-2xl rounded-tl-sm px-4 py-3">
            <div class="flex gap-1 items-center h-5">
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></div>
                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></div>
            </div>
        </div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return id;
}

function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}


window.addEventListener('load', () => {
    const container = document.getElementById('chat-messages');
    container.scrollTop = container.scrollHeight;
});
</script>

@endsection