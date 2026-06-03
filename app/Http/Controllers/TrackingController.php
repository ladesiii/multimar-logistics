<?php

namespace App\Http\Controllers;

use App\Clases\Utilitat;
use App\Http\Resources\TrackingResource;
use App\Models\Oferta;
use App\Models\TrackingStep;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrackingController extends Controller
{
    // Devuelve una vista simplificada de tracking para ofertas aceptadas.
    public function listarTracking(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'tracking' => [],
            ]);
        }

        $query = Oferta::with([
            'tipusTransport:id,tipus',
            'tipusIncoterm:id,codi,nom',
            'portOrigen:id,nom',
            'portDesti:id,nom',
            'aeroportOrigen:id,codi,nom',
            'aeroportDesti:id,codi,nom',
            'trackingStep:id,nom',
        ])
            ->where('estat_oferta_id', 2)
            ->orderByDesc('id');

        if ($this->esUsuarioOperador($user)) {
            $query->where('operador_id', $user->id);
        } elseif ($this->esUsuarioCliente($user)) {
            $clientId = $this->obtenerIdClientePorUsuario($user);

            if (! $clientId) {
                return response()->json([
                    'tracking' => [],
                ]);
            }

            $query->where('client_id', $clientId);
        }

        $offers = $query->get();
        $trackingState = $this->resolverNombrePasoTrackingInicial();

        $tracking = [];

        foreach ($offers as $offer) {
            $tracking[] = (new TrackingResource($offer))->withDefaultState($trackingState);
        }

        return response()->json([
            'tracking' => $tracking,
        ]);
    }

    // Devuelve los pasos de tracking del incoterm asociado a la oferta.
    public function listarPasosTracking(Oferta $offer): JsonResponse
    {
        $incotermId = $offer->tipus_incoterm_id;

        if (! $incotermId) {
            return response()->json(['steps' => []]);
        }

        try {
            // Obtiene los pasos de tracking asociados al incoterm mediante la tabla incoterms_passos
            $steps = TrackingStep::join('incoterms_passos', 'tracking_steps.id', '=', 'incoterms_passos.tracking_step_id')
                ->where('incoterms_passos.tipus_incoterm_id', $incotermId)
                ->orderBy('tracking_steps.ordre')
                ->orderBy('tracking_steps.id')
                ->get(['tracking_steps.id', 'tracking_steps.nom', 'tracking_steps.ordre']);

            return response()->json(['steps' => $steps]);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => ! empty($mensaje) ? $mensaje : 'Error interno al obtener los pasos de tracking.',
            ], 500);
        }
    }

    // Actualiza el paso de tracking de una oferta (solo admin).
    public function actualizarPasoTracking(Request $request, Oferta $offer): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tracking_step_id' => ['required', 'integer', Rule::exists('tracking_steps', 'id')],
            ]);

            $offer->update(['tracking_step_id' => $validated['tracking_step_id']]);

            return response()->json(['success' => true]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);

        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => ! empty($mensaje) ? $mensaje : 'Error interno al actualizar el paso de tracking.',
            ], 500);
        }
    }

    // Busca el ID del primer paso de tracking para usarlo al aceptar una oferta.
    public function resolverIdPasoTrackingInicial(): ?int
    {
        $firstStep = $this->obtenerPrimerPasoTracking();

        return $firstStep?->id ? (int) $firstStep->id : null;
    }

    private function resolverNombrePasoTrackingInicial(): string
    {
        $firstStep = $this->obtenerPrimerPasoTracking();
        $firstStepName = trim((string) ($firstStep?->nom ?? ''));

        return $firstStepName !== ''
            ? $firstStepName
            : 'En tracking';
    }

    private function obtenerPrimerPasoTracking(): ?TrackingStep
    {
        return TrackingStep::query()
            ->orderBy('ordre', 'asc')
            ->orderBy('id')
            ->first(['id', 'nom']);
    }

    private function esUsuarioOperador($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->usuarioTieneRol($user, 2, ['operador', 'operator']);
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
}
