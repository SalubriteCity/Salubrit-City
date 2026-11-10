<?php

namespace App\Http\Middleware;

use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationMiddleware
{
    public function __construct(private TokenService $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $session = $token === null ? null : $this->tokens->authenticate($token);

        if ($session === null || $session->user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->setUserResolver(fn () => $session->user);
        $request->attributes->set('auth_session', $session);

        return $next($request);
    }
}
