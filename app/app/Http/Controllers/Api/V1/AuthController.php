<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\RegisterRequest;

use App\Http\Resources\AuthUserResource;

use App\Services\RegistrationService;

use Illuminate\Http\JsonResponse;


class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegistrationService $registration): JsonResponse
    {
        $user = $registration->register($request->validated());

        return response()->json([
            'message' => 'Account created. Check your email for a verification code.',
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ], 201);
    }

}
