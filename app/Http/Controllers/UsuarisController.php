<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarisController extends Controller
{
    private function formatearUsuario(Usuari $user): array  // Función para formatear el usuario en index, store y update, evitando repetir código.
    {
        return [
            'id' => $user->id,
            'nom' => $user->nom,
            'cognoms' => $user->cognoms,
            'nom_complet' => trim($user->nom . ' ' . $user->cognoms),
            'email' => $user->correu,
            'rol_id' => $user->rol_id,
            'rol' => $user->rol?->rol ?? 'Sin rol',
        ];
    }

    public function index(): JsonResponse // Devuelve la lista de usuarios con su rol, formateada para el frontend.
    {
        $users = Usuari::with('rol')
            ->orderBy('id')
            ->get()
            ->map(fn (Usuari $user) => $this->formatearUsuario($user));

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse // Crea un nuevo usuario con los datos recibidos, validando que el email sea único y que el rol sea válido.
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
            'user' => $this->formatearUsuario($user),
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
            'user' => $this->formatearUsuario($user),
        ]);
    }

    public function destroy(Usuari $user): JsonResponse // Elimina un usuario existente, devolviendo un mensaje de confirmación.
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
