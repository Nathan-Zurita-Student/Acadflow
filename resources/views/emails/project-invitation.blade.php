<x-mail::message>
# Você foi convidado! 🎉

Olá, {{ $name }}! **{{ $inviter }}** convidou você para participar do projeto **"{{ $project }}"** como **{{ $role }}** no AcadFlow.

<x-mail::button :url="$url" color="primary">Ver convite</x-mail::button>

Clique no botão acima para ver os detalhes e aceitar o convite — ele expira em 7 dias.

Até já,<br>
Equipe AcadFlow
</x-mail::message>
