<x-mail::message>
# Assinatura cancelada

Olá, {{ $name }}. Sua assinatura **{{ $plan }}** foi cancelada.

@if($expires)
Você continua com acesso aos recursos até **{{ $expires }}**. Depois disso, sua conta volta ao plano gratuito.
@endif

Mudou de ideia? Você pode reativar quando quiser:

<x-mail::button :url="$url" color="primary">Reativar assinatura</x-mail::button>

Sentiremos sua falta. 💜<br>
Equipe AcadFlow
</x-mail::message>
