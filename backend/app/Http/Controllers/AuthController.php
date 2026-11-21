<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // mismo mensaje para email o contraseña mala, así no se sabe cuál existe
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw new ApiException(401, 'Correo o contraseña incorrectos.');
        }

        if (! $user->is_active) {
            throw new ApiException(403, 'Tu usuario está desactivado.');
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
