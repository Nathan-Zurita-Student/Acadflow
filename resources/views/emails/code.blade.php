<x-mail::message>
# {{ $heading }}

{{ $intro }}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 26px 0;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td align="center" style="background:#eef2ff; border:1px solid #c7d2fe; border-radius:14px; padding:18px 28px;">
<span style="font-size:34px; font-weight:700; letter-spacing:10px; color:#4f46e5; font-family:'SFMono-Regular',Consolas,'Liberation Mono',Menlo,monospace;">{{ $code }}</span>
</td></tr>
</table>
</td></tr>
</table>

Este código expira em **{{ $minutes }} minutos**.

@if(!empty($outro))
{{ $outro }}
@endif

Obrigado,<br>
Equipe AcadFlow
</x-mail::message>
