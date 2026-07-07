<x-mail::message>
# Pagamento pendente ⚠️

Olá, {{ $name }}. Não identificamos o pagamento da sua assinatura **{{ $plan }}**. Para manter seus recursos ativos, regularize o quanto antes.

<x-mail::button :url="$url" color="error">Regularizar pagamento</x-mail::button>

Se você já pagou, pode ignorar este aviso — a confirmação pode levar alguns minutos.

Equipe AcadFlow
</x-mail::message>
