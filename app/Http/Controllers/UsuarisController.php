<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsuarisController extends Controller
{
    public function index(): JsonResponse
    {
        $users = Usuari::with('rol')
            ->orderBy('id')
            ->get()
            ->map(function (Usuari $user) {
                return [
                    'id' => $user->id,
                    'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
                    'email' => $user->correu,
                    'rol' => $user->rol?->rol ?? 'Sin rol',
                ];
            });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'cognoms' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:50', 'unique:usuaris,correu'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $user = Usuari::create([
            'nom' => $validated['nom'],
            'cognoms' => $validated['cognoms'],
            'correu' => $validated['email'],
            'contrasenya' => $validated['password'],
            'rol_id' => 2,
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => [
                'id' => $user->id,
                'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
                'email' => $user->correu,
                'rol' => $user->rol?->rol ?? 'Sin rol',
            ],
        ], 201);
    }
}