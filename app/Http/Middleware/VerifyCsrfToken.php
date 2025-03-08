<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfToken
{
    /**
     * CSRF doğrulamasından muaf tutulacak URL'ler.
     *
     * @var array<int, string>
     */
    protected $except = [
        'chats/*',
        'broadcasting/auth',
        'api/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // CSRF korumasını devre dışı bırak
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // CSRF token kontrolü
        if ($request->method() !== 'GET' && $request->method() !== 'HEAD') {
            if (!$request->hasHeader('X-CSRF-TOKEN') && !$request->hasCookie('XSRF-TOKEN')) {
                abort(419, 'CSRF token eksik. Lütfen sayfayı yenileyin.');
            }
        }

        return $next($request);
    }

    /**
     * İsteğin muaf URL'lerden olup olmadığını kontrol et.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
