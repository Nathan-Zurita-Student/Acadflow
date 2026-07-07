<x-mail::message>
# {{ count($tasks) === 1 ? 'Uma tarefa vence amanhã' : count($tasks).' tarefas vencem amanhã' }} ⏰

Olá, {{ $name }}! Um lembrete rápido do que vence amanhã:

@foreach($tasks as $t)
- **{{ $t['title'] }}** — {{ $t['project'] }}
@endforeach

<x-mail::button :url="$url" color="primary">Ver minhas tarefas</x-mail::button>

Bons estudos,<br>
Equipe AcadFlow
</x-mail::message>
