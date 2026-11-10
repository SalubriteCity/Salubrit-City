<?php

namespace App\Http\Middleware;

use App\Services\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizationMiddleware
{
    public function __construct(private AuthorizationService $authorization) {}

    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions,
    ): Response {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        foreach ($permissions as $permission) {
            if (! $this->authorization->allows($user, $permission)) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        }

        return $next($request);
    }
}
