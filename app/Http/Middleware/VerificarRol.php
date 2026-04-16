<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $this->respuestaProhibido($request, 'No autenticado.');
        }

        // Convierte los roles recibidos en una lista normalizada (admin/operador/cliente).
        $rolesNormalizados = array_values(array_filter(array_map(
            fn (string $rol) => $this->normalizarNombreRol($rol),
            $roles
        )));

        // Si no se pide ningun rol concreto, deja pasar.
        if ($rolesNormalizados === []) {
            return $next($request);
        }

        // Solo deja pasar si el usuario tiene al menos uno de los roles esperados.
        if ($this->usuarioTieneAlgunRol($usuario, $rolesNormalizados)) {
            return $next($request);
        }

        return $this->respuestaProhibido($request, 'No tienes permiso para acceder a este recurso.');
    }

    private function usuarioTieneAlgunRol($usuario, array $rolesEsperados): bool
    {
        $nombreRol = $this->normalizarNombreRol((string) ($usuario->rol?->rol ?? ''));
        $idRol = (int) ($usuario->rol_id ?? 0);

        foreach ($rolesEsperados as $rolEsperado) {
            if ($rolEsperado === 'admin' && ($nombreRol === 'admin' || $idRol === 1)) {
                return true;
            }

            if ($rolEsperado === 'operador' && ($nombreRol === 'operador' || $idRol === 2)) {
                return true;
            }

            if ($rolEsperado === 'cliente' && ($nombreRol === 'cliente' || $idRol === 3)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarNombreRol(string $valor): string
    {
        $rolLimpio = strtolower(trim($valor));

        if (str_contains($rolLimpio, 'admin')) {
            return 'admin';
        }

        if (str_contains($rolLimpio, 'operador') || str_contains($rolLimpio, 'operator')) {
            return 'operador';
        }

        if (str_contains($rolLimpio, 'client')) {
            return 'cliente';
        }

        return $rolLimpio;
    }

    private function respuestaProhibido(Request $request, string $mensaje): Response
    {
        // Si es API (o espera JSON), devuelve error JSON; en web normal, aborta con 403.
        if ($request->expectsJson() || $request->is('api/*')) {
            return new JsonResponse([
                'message' => $mensaje,
            ], 403);
        }

        abort(403, $mensaje);
    }
}
