<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
                    'nom' => $user->nom,
                    'cognoms' => $user->cognoms,
                    'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
                    'email' => $user->correu,
                    'rol_id' => $user->rol_id,
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
            'password' => ['required', 'string', 'max:255'],
            'rol_id' => ['required', 'integer', 'in:1,2'],
        ]);

        $user = Usuari::create([
            'nom' => $validated['nom'],
            'cognoms' => $validated['cognoms'],
            'correu' => $validated['email'],
            'contrasenya' => $validated['password'],
            'rol_id' => $validated['rol_id'],
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'cognoms' => $user->cognoms,
                'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
                'email' => $user->correu,
                'rol_id' => $user->rol_id,
                'rol' => $user->rol?->rol ?? 'Sin rol',
            ],
        ], 201);
    }

    public function update(Request $request, Usuari $user): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'cognoms' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:50',
                Rule::unique('usuaris', 'correu')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'max:255'],
            'rol_id' => ['required', 'integer', 'in:1,2'],
        ]);

        $user->nom = $validated['nom'];
        $user->cognoms = $validated['cognoms'];
        $user->correu = $validated['email'];
        $user->rol_id = $validated['rol_id'];

        if (!empty($validated['password'])) {
            $user->contrasenya = $validated['password'];
        }

        $user->save();
        $user->load('rol');

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'cognoms' => $user->cognoms,
                'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
                'email' => $user->correu,
                'rol_id' => $user->rol_id,
                'rol' => $user->rol?->rol ?? 'Sin rol',
            ],
        ]);
    }

    public function destroy(Usuari $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
