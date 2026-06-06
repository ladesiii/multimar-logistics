<?php

namespace App\Http\Controllers;

use App\Clases\Utilitat;
use App\Http\Resources\UserResource;
use App\Models\Usuari;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsuarisController extends Controller
{
    public function index(): JsonResponse // Devuelve la lista de usuarios con su rol, formateada para el frontend.
    {
        $users = Usuari::with('rol')
            ->orderBy('id')
            ->get();

        return response()->json([
            'users' => UserResource::collection($users),
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

        $user = new Usuari();
        $user->nom = $validated['nom'];
        $user->cognoms = $validated['cognoms'];
        $user->correu = $validated['email'];
        $user->contrasenya = $validated['password'];
        $user->rol_id = $validated['rol_id'];

        try {
            $user->save();

            return response()->json([
                'message' => 'Usuario creado correctamente.',
                'user' => new UserResource($user->loadMissing('rol')),
            ], 201);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);
            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al crear el usuario.',
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = Usuari::findOrFail($id);

        try {
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
                'user' => new UserResource($user),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);
            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al actualizar el usuario.',
            ], 500);
        }
    }

    public function destroy($id): JsonResponse // Elimina un usuario existente, devolviendo un mensaje de confirmación.
    {
        $user = Usuari::findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
