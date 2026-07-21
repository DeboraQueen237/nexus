<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Micro/caméra autorisés uniquement sur les pages du module Réunions
        // (appels vidéo/vocaux), fermés partout ailleurs par défaut.
        $isMeetingRoute = $request->routeIs('meetings.*');
        $response->headers->set('Permissions-Policy', $isMeetingRoute
            ? 'geolocation=(), microphone=(self), camera=(self)'
            : 'geolocation=(), microphone=(), camera=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
