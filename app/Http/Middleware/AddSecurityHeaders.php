<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if (! $request->is('login', 'robots.txt', 'sitemap.xml', 'llms.txt')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $existingCsp = (string) $response->headers->get('Content-Security-Policy', '');
        if ($existingCsp === '' || str_contains($existingCsp, "script-src 'none'")) {
            $response->headers->set('Content-Security-Policy', $this->buildCsp());
        }

        return $response;
    }

    private function buildCsp(): string
    {
        $isLocalDev = ! app()->isProduction();

        $viteHttp = $isLocalDev ? ' http://localhost:5173 http://127.0.0.1:5173' : '';
        $viteWs = $isLocalDev ? ' ws://localhost:5173 ws://127.0.0.1:5173' : '';

        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$viteHttp}",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.bunny.net{$viteHttp}",
            "script-src 'self' 'unsafe-inline'{$viteHttp}",
            "script-src-elem 'self' 'unsafe-inline'{$viteHttp}",
            "connect-src 'self'{$viteHttp}{$viteWs}",
        ];

        return implode('; ', $directives);
    }
}
