<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança (OWASP) aplicados a todas as respostas.
 *
 * A CSP é enviada em modo Report-Only por padrão (não quebra a SPA); defina
 * CSP_ENFORCE=true para passar a bloquear após validar o relatório em produção.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), browsing-topics=()');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS só faz sentido sob HTTPS.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $header = config('app.csp_enforce', env('CSP_ENFORCE', false))
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($header, $this->contentSecurityPolicy());

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $connect = ['\'self\''];

        // Permite o WebSocket do Reverb (notificações em tempo real), se configurado.
        if ($host = env('REVERB_HOST')) {
            $scheme = env('REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws';
            $port   = env('REVERB_PORT');
            $origin = $scheme.'://'.$host.($port ? ':'.$port : '');
            $connect[] = $origin;
            $connect[] = (env('REVERB_SCHEME', 'https') === 'https' ? 'https' : 'http').'://'.$host.($port ? ':'.$port : '');
        }

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            'connect-src '.implode(' ', $connect),
        ];

        return implode('; ', $directives);
    }
}
