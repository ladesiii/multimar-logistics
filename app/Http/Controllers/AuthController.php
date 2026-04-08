<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = Usuari::with('rol')
            ->where('correu', $credentials['email'])
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Las credenciales no son válidas.',
            ], 401);
        }

        if (! Hash::check($credentials['password'], $user->contrasenya)) {
            return response()->json([
                'message' => 'Las credenciales no son válidas.',
            ], 401);
        }

        $token = $user->createToken('multimar-api')->plainTextToken;

        return response()->json([
            'message' => 'Login correcto.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->correu,
                'name' => trim($user->nom . ' ' . $user->cognoms),
                'nom' => $user->nom,
                'cognoms' => $user->cognoms,
                'rol_id' => $user->rol_id,
                'rol' => $user->rol?->rol,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return response()->json([
            'user' => $user->load('rol'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
