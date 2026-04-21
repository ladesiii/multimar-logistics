<?php

namespace App\Http\Controllers;

use App\Models\Usuari;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    // Convierte el modelo Usuari al formato de datos que devolvemos en login y perfil.
    private function formatearUsuario(Usuari $usuario): array
    {
        return [
            'id' => $usuario->id,
            'email' => $usuario->correu,
            'name' => trim($usuario->nom . ' ' . $usuario->cognoms),
            'nom' => $usuario->nom,
            'cognoms' => $usuario->cognoms,
            'rol_id' => $usuario->rol_id,
            'rol' => $usuario->rol?->rol,
        ];
    }

    // Respuesta común para credenciales inválidas en login.
    private function respuestaCredencialesInvalidas(): JsonResponse
    {
        return response()->json([
            'message' => 'Las credenciales no son válidas.',
        ], 401);
    }


    public function login(Request $request): JsonResponse
    {
        try {
            $credenciales = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            $usuario = Usuari::with('rol')
                ->where('correu', $credenciales['email'])
                ->first();

            if (! $usuario || ! Hash::check($credenciales['password'], $usuario->contrasenya)) {
                return $this->respuestaCredencialesInvalidas();
            }

            $token = $usuario->createToken('multimar-api')->plainTextToken; // Crea un token de acceso para el usuario autenticado.

            return response()->json([
                'message' => 'Login correcto.',
                'token_type' => 'Bearer',
                'token' => $token,
                'user' => $this->formatearUsuario($usuario),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al iniciar sesión.',
            ], 500);
        }
    }

    public function usuarioAutenticado(Request $request): JsonResponse // Devuelve los datos del usuario autenticado (usado para mostrar el perfil).
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return response()->json([
            'user' => $user->load('rol'),
        ]);
    }

    public function cerrarSesion(Request $request): JsonResponse // Cierra la sesión del usuario autenticado.
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function actualizarPerfil(Request $request): JsonResponse // Permite al usuario autenticado actualizar su perfil (nombre, apellidos, email y/o contraseña).
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'nom' => ['required', 'string', 'max:50'],
                'cognoms' => ['required', 'string', 'max:50'],
                'email' => [
                    'required',
                    'email',
                    'max:100',
                    Rule::unique('usuaris', 'correu')->ignore($user->id),
                ],
                'current_password' => ['nullable', 'string', 'max:255'],
                'new_password' => ['nullable', 'string', 'min:6', 'max:255'],
                'new_password_confirmation' => ['nullable', 'string', 'max:255'],
            ]);

            if (! empty($validated['new_password'])) {
                if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->contrasenya)) {
                    return response()->json([
                        'message' => 'La contraseña actual no es correcta.',
                    ], 422);
                }

                if (($validated['new_password'] ?? null) !== ($validated['new_password_confirmation'] ?? null)) {
                    return response()->json([
                        'message' => 'La nueva contraseña y su confirmación no coinciden.',
                    ], 422);
                }
            }

            $user->nom = $validated['nom'];
            $user->cognoms = $validated['cognoms'];
            $user->correu = $validated['email'];

            if (! empty($validated['new_password'])) {
                $user->contrasenya = $validated['new_password'];
            }

            $user->save();
            $user->load('rol');

            return response()->json([
                'message' => 'Perfil actualizado correctamente.',
                'user' => $this->formatearUsuario($user),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al actualizar el perfil.',
            ], 500);
        }
    }

    public function verificarContrasenaPerfil(Request $request): JsonResponse // Endpoint para verificar la contraseña actual del perfil (usado antes de permitir cambiar la contraseña).
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        try {
            $validated = $request->validate([
                'current_password' => ['required', 'string', 'max:255'],
            ]);

            $isValid = Hash::check($validated['current_password'], $user->contrasenya);

            if (! $isValid) {
                return response()->json([
                    'message' => 'La contraseña actual no es correcta.',
                    'valid' => false,
                ], 422);
            }

            return response()->json([
                'message' => 'Contraseña verificada correctamente.',
                'valid' => true,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al verificar la contraseña.',
            ], 500);
        }
    }
}
