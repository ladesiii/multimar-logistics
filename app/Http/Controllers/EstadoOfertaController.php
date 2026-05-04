<?php

namespace App\Http\Controllers;

use App\Clases\Utilitat;
use App\Http\Resources\OfferStatusResource;
use App\Models\Oferta;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EstadoOfertaController extends Controller
{
    // Actualiza el estado de una oferta sin depender de OfertesController.
    public function actualizarEstado(Request $request, int $offer): JsonResponse
    {
        $oferta = Oferta::findOrFail($offer);

        if (! $this->puedeAccederOferta($request, $oferta)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        try {
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
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al actualizar el estado de la oferta.',
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

        if (! $this->esUsuarioCliente($user)) {
            return false;
        }

        $clientId = $this->obtenerIdClientePorUsuario($user);

        if (! $clientId) {
            return false;
        }

        return (int) $oferta->client_id === (int) $clientId;
    }

    private function esUsuarioAdmin($user): bool
    {
        return (int) ($user?->id ?? 0) === 1
            || $this->obtenerRolUsuario($user) === 1;
    }

    private function esUsuarioCliente($user): bool
    {
        return $this->obtenerRolUsuario($user) === 3;
    }

    private function obtenerRolUsuario($user): ?int
    {
        if (! $user) {
            return null;
        }

        $role = $user->rol_id ?? null;

        return is_null($role) ? null : (int) $role;
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
