<x-mail::message>
# Seu resumo da semana 📊

Olá, {{ $name }}! Veja como foi sua semana no AcadFlow:

<x-mail::table>
| Resumo | |
| :--- | ---: |
| ✅ Concluídas (últimos 7 dias) | **{{ $stats['completed'] }}** |
| 📋 Pendentes | **{{ $stats['pending'] }}** |
| ⚠️ Atrasadas | **{{ $stats['overdue'] }}** |
| 📅 Vencem nos próximos 7 dias | **{{ $stats['upcoming'] }}** |
</x-mail::table>

<x-mail::button :url="$url" color="primary">Abrir o Dashboard</x-mail::button>

Continue no ritmo — você está indo bem! 💪<br>
Equipe AcadFlow
</x-mail::message>
