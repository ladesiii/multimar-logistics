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
        $user = $request->user();

        if (! $user) {
            return $this->forbidden($request, 'No autenticado.');
        }

        $normalizedRoles = array_values(array_filter(array_map(
            fn (string $role) => $this->normalizeRoleName($role),
            $roles
        )));

        if ($normalizedRoles === []) {
            return $next($request);
        }

        if ($this->userHasAnyRole($user, $normalizedRoles)) {
            return $next($request);
        }

        return $this->forbidden($request, 'No tienes permiso para acceder a este recurso.');
    }

    private function userHasAnyRole($user, array $expectedRoles): bool
    {
        $roleName = $this->normalizeRoleName((string) ($user->rol?->rol ?? ''));
        $roleId = (int) ($user->rol_id ?? 0);

        foreach ($expectedRoles as $expectedRole) {
            if ($expectedRole === 'admin' && ($roleName === 'admin' || $roleId === 1)) {
                return true;
            }

            if ($expectedRole === 'operador' && ($roleName === 'operador' || $roleId === 2)) {
                return true;
            }

            if ($expectedRole === 'cliente' && ($roleName === 'cliente' || $roleId === 3)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeRoleName(string $value): string
    {
        $clean = strtolower(trim($value));

        if (str_contains($clean, 'admin')) {
            return 'admin';
        }

        if (str_contains($clean, 'operador') || str_contains($clean, 'operator')) {
            return 'operador';
        }

        if (str_contains($clean, 'client')) {
            return 'cliente';
        }

        return $clean;
    }

    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return new JsonResponse([
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
