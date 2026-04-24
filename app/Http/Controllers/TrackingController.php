<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackingResource;
use App\Models\Oferta;
use App\Models\TrackingStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $tracking = $offers
            ->map(fn (Oferta $offer) => (new TrackingResource($offer))->withDefaultState($trackingState))
            ->values();

        return response()->json([
            'tracking' => $tracking,
        ]);
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
            ->orderBy('ordre')
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
