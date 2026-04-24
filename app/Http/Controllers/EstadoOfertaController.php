<?php

namespace App\Http\Controllers;

use App\Http\Resources\OfferStatusResource;
use App\Models\Oferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EstadoOfertaController extends Controller
{
    // Actualiza el estado de una oferta sin depender de OfertesController.
    public function actualizarEstado(Request $request, int $offer): JsonResponse
    {
        $oferta = Oferta::find($offer);

        if (! $oferta) {
            return response()->json([
                'message' => 'Oferta no encontrada.',
            ], 404);
        }

        if (! $this->puedeAccederOferta($request, $oferta)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        try {
            if (! $this->puedeGestionarEstadoOferta($request)) {
                return response()->json([
                    'message' => 'Solo el cliente puede aceptar o rechazar ofertas.',
                ], 403);
            }

            $validated = $request->validate([
                'estat_oferta_id' => ['required', 'integer', Rule::in([2, 3])],
                'rao_rebuig' => ['nullable', 'string', 'max:255', 'required_if:estat_oferta_id,3'],
            ]);

            if ((int) $oferta->estat_oferta_id !== 1) {
                return response()->json([
                    'message' => 'Esta oferta ya fue gestionada y no puede cambiar de estado.',
                ], 422);
            }

            $oferta->estat_oferta_id = (int) $validated['estat_oferta_id'];
            $tieneTrackingStep = array_key_exists('tracking_step_id', $oferta->getAttributes());

            if ((int) $validated['estat_oferta_id'] === 3) {
                $oferta->rao_rebuig = trim((string) ($validated['rao_rebuig'] ?? ''));

                if ($tieneTrackingStep) {
                    $oferta->tracking_step_id = null;
                }
            }

            if ((int) $validated['estat_oferta_id'] === 2) {
                $oferta->rao_rebuig = null;

                if ($tieneTrackingStep) {
                    $oferta->tracking_step_id = $this->resolverIdPasoTrackingInicial();
                }
            }

            $oferta->save();
            $oferta->refresh();

            return response()->json([
                'message' => (int) $validated['estat_oferta_id'] === 2
                    ? 'Oferta aceptada correctamente.'
                    : 'Oferta rechazada correctamente.',
                'offer' => new OfferStatusResource($oferta),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al actualizar el estado de la oferta.',
            ], 500);
        }
    }

    private function puedeAccederOferta(Request $request, Oferta $oferta): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($this->esUsuarioAdmin($user)) {
            return true;
        }

        if ($this->esUsuarioCliente($user)) {
            $clientId = $this->obtenerIdClientePorUsuario($user);

            if (! $clientId) {
                return false;
            }

            return (int) $oferta->client_id === (int) $clientId;
        }

        return false;
    }

    private function puedeGestionarEstadoOferta(Request $request): bool
    {
        $user = $request->user();

        return $this->esUsuarioCliente($user) || $this->esUsuarioAdmin($user);
    }

    private function esUsuarioAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $user->id === 1
            || $this->usuarioTieneRol($user, 1, ['admin']);
    }

    private function esUsuarioCliente($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->usuarioTieneRol($user, 3, ['client']);
    }

    private function usuarioTieneRol($user, int $roleId, array $roleKeywords): bool
    {
        if ((int) ($user->rol_id ?? 0) === $roleId) {
            return true;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        foreach ($roleKeywords as $keyword) {
            if (str_contains($roleName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function obtenerIdClientePorUsuario($user): ?int
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('client');

        return $user->client?->id ? (int) $user->client->id : null;
    }

    private function resolverIdPasoTrackingInicial(): ?int
    {
        return app(TrackingController::class)->resolverIdPasoTrackingInicial();
    }
}
