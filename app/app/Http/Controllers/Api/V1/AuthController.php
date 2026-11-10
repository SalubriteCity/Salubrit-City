<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;


use App\Http\Requests\Auth\RegisterRequest;

use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\AuthUserResource;

use App\Services\EmailVerificationService;
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

    public function verifyEmail(
        VerifyEmailRequest $request,
        EmailVerificationService $verification,
    ): JsonResponse {
        $data = $request->validated();
        $user = $verification->verify($data['email'], $data['otp']);

        return response()->json([
            'message' => 'Email address verified.',
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ]);
    }

}
