<x-mail::message>
# Assinatura confirmada! ✨

Olá, {{ $name }}! Seu pagamento foi confirmado e o plano **{{ $plan }}** já está ativo. Obrigado por apoiar o AcadFlow!

@if($expires)
Seu acesso está garantido até **{{ $expires }}** e será renovado automaticamente.
@endif

<x-mail::button :url="$url" color="primary">Gerenciar assinatura</x-mail::button>

Bons estudos,<br>
Equipe AcadFlow
</x-mail::message>
