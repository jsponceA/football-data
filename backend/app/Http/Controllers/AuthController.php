<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AuthController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    // responde igual que el login para que el front deje a la persona dentro sin pedirle que entre otra vez
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->users->register($request->validated());

        return response()->json([
            'token' => $user->createToken('spa')->accessToken,
            'user' => new UserResource($user),
        ], HttpResponse::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // mismo mensaje para email o contraseña mala, así no se sabe cuál existe
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw new ApiException(HttpResponse::HTTP_UNAUTHORIZED,'Correo o contraseña incorrectos.');
        }

        if (! $user->is_active) {
            throw new ApiException(HttpResponse::HTTP_FORBIDDEN,'Tu usuario está desactivado.');
        }

        return response()->json([
            'token' => $user->createToken('spa')->accessToken,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->token()->revoke();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
