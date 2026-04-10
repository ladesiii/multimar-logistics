<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OfertesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $offersQuery = Oferta::with([
            'client:id,nom_empresa',
            'operador:id,nom,cognoms',
            'estatOferta:id,estat',
            'tipusTransport:id,tipus',
            'tipusIncoterm:id,codi,nom',
        ])
            ->orderByDesc('id');

        if (! $this->isAdminUser($user)) {
            $offersQuery->where('operador_id', $user?->id);
        }

        $offers = $offersQuery
            ->get()
            ->map(fn (Oferta $offer) => $this->serializeOffer($offer));

        return response()->json([
            'offers' => $offers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->isAdminUser($user) && $user) {
            $request->merge([
                'operador_id' => $user->id,
            ]);
        }

        $validated = $request->validate($this->rules());

        $offer = Oferta::create($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus', 'tipusIncoterm:id,codi,nom']);

        return response()->json([
            'message' => 'Oferta creada correctamente.',
            'offer' => $this->serializeOffer($offer),
        ], 201);
    }

    public function update(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->canAccessOffer($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        $user = $request->user();

        if (! $this->isAdminUser($user) && $user) {
            $request->merge([
                'operador_id' => $user->id,
            ]);
        }

        $validated = $request->validate($this->rules($offer));

        $offer->update($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus', 'tipusIncoterm:id,codi,nom']);

        return response()->json([
            'message' => 'Oferta actualizada correctamente.',
            'offer' => $this->serializeOffer($offer),
        ]);
    }

    public function show(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->canAccessOffer($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para ver esta oferta.',
            ], 403);
        }

        return response()->json([
            'offer' => $this->serializeOfferDetail($offer->id),
        ]);
    }

    public function updateStatus(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->canAccessOffer($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        $validated = $request->validate([
            'estat_oferta_id' => ['required', 'integer', Rule::in([2, 3])],
            'rao_rebuig' => ['nullable', 'string', 'max:255', 'required_if:estat_oferta_id,3'],
        ]);

        if ((int) $offer->estat_oferta_id !== 1) {
            return response()->json([
                'message' => 'Esta oferta ya fue gestionada y no puede cambiar de estado.',
            ], 422);
        }

        $offer->estat_oferta_id = (int) $validated['estat_oferta_id'];

        if ((int) $validated['estat_oferta_id'] === 3) {
            $offer->rao_rebuig = trim((string) ($validated['rao_rebuig'] ?? ''));
        }

        if ((int) $validated['estat_oferta_id'] === 2) {
            $offer->rao_rebuig = null;
        }

        $offer->save();

        return response()->json([
            'message' => (int) $validated['estat_oferta_id'] === 2
                ? 'Oferta aceptada correctamente.'
                : 'Oferta rechazada correctamente.',
            'offer' => $this->serializeOfferDetail($offer->id),
        ]);
    }

    public function destroy(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->canAccessOffer($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar esta oferta.',
            ], 403);
        }

        $offer->delete();

        return response()->json([
            'message' => 'Oferta eliminada correctamente.',
        ]);
    }

    private function rules(?Oferta $offer = null): array
    {
        return [
            'tipus_transport_id' => ['required', 'integer', Rule::exists('tipus_transports', 'id')],
            'tipus_fluxe_id' => ['required', 'integer', Rule::exists('tipus_fluxes', 'id')],
            'tipus_carrega_id' => ['required', 'integer', Rule::exists('tipus_carrega', 'id')],
            'tipus_incoterm_id' => ['required', 'integer', Rule::exists('tipus_incoterms', 'id')],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'agent_comercial_id' => ['nullable', 'integer', Rule::exists('usuaris', 'id')],
            'operador_id' => ['required', 'integer', Rule::exists('usuaris', 'id')],
            'estat_oferta_id' => ['required', 'integer', Rule::exists('estats_ofertes', 'id')],
            'tipus_validacio_id' => ['required', 'integer', Rule::exists('tipus_validacions', 'id')],
            'transportista_id' => ['nullable', 'integer', Rule::exists('transportistes', 'id')],
            'linia_transport_maritim_id' => ['nullable', 'integer', Rule::exists('linies_transport_maritim', 'id')],
            'port_origen_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'port_desti_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'aeroport_origen_id' => ['nullable', 'integer', Rule::exists('aeroports', 'id')],
            'aeroport_desti_id' => ['nullable', 'integer', Rule::exists('aeroports', 'id')],
            'tipus_contenidor_id' => ['nullable', 'integer', Rule::exists('tipus_contenidors', 'id')],
            'pes_brut' => ['nullable', 'numeric'],
            'volum' => ['nullable', 'numeric'],
            'comentaris' => ['nullable', 'string', 'max:255'],
            'rao_rebuig' => ['nullable', 'string', 'max:255'],
            'data_creacio' => ['required', 'date'],
            'data_validessa_inicial' => ['nullable', 'date'],
            'data_validessa_final' => ['nullable', 'date', 'after_or_equal:data_validessa_inicial'],
            'preu' => ['nullable', 'integer'],
        ];
    }

    private function serializeOffer(Oferta $offer): array
    {
        $incotermCode = trim((string) ($offer->tipusIncoterm?->codi ?? ''));
        $incotermName = trim((string) ($offer->tipusIncoterm?->nom ?? ''));
        $incotermLabel = trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);

        return [
            'id' => $offer->id,
            'tipus_transport_id' => $offer->tipus_transport_id,
            'tipus_fluxe_id' => $offer->tipus_fluxe_id,
            'tipus_carrega_id' => $offer->tipus_carrega_id,
            'tipus_incoterm_id' => $offer->tipus_incoterm_id,
            'client_id' => $offer->client_id,
            'operador_id' => $offer->operador_id,
            'estat_oferta_id' => $offer->estat_oferta_id,
            'tipus_validacio_id' => $offer->tipus_validacio_id,
            'data_creacio' => optional($offer->data_creacio)->format('Y-m-d'),
            'preu' => $offer->preu,
            'client' => $offer->client?->nom_empresa,
            'operador' => trim(($offer->operador?->nom ?? '') . ' ' . ($offer->operador?->cognoms ?? '')),
            'estat' => $offer->estatOferta?->estat,
            'tipus_transport' => $offer->tipusTransport?->tipus,
            'tipus_incoterm' => $incotermLabel,
        ];
    }

    private function serializeOfferDetail(int $offerId): array
    {
        $offer = DB::table('ofertes as o')
            ->leftJoin('clients as c', 'o.client_id', '=', 'c.id')
            ->leftJoin('usuaris as op', 'o.operador_id', '=', 'op.id')
            ->leftJoin('usuaris as ac', 'o.agent_comercial_id', '=', 'ac.id')
            ->leftJoin('tipus_transports as tt', 'o.tipus_transport_id', '=', 'tt.id')
            ->leftJoin('tipus_fluxes as tf', 'o.tipus_fluxe_id', '=', 'tf.id')
            ->leftJoin('tipus_carrega as tc', 'o.tipus_carrega_id', '=', 'tc.id')
            ->leftJoin('tipus_incoterms as ti', 'o.tipus_incoterm_id', '=', 'ti.id')
            ->leftJoin('tipus_validacions as tv', 'o.tipus_validacio_id', '=', 'tv.id')
            ->leftJoin('estats_ofertes as eo', 'o.estat_oferta_id', '=', 'eo.id')
            ->leftJoin('transportistes as tr', 'o.transportista_id', '=', 'tr.id')
            ->leftJoin('linies_transport_maritim as ltm', 'o.linia_transport_maritim_id', '=', 'ltm.id')
            ->leftJoin('ports as po', 'o.port_origen_id', '=', 'po.id')
            ->leftJoin('ports as pd', 'o.port_desti_id', '=', 'pd.id')
            ->leftJoin('aeroports as ao', 'o.aeroport_origen_id', '=', 'ao.id')
            ->leftJoin('aeroports as ad', 'o.aeroport_desti_id', '=', 'ad.id')
            ->leftJoin('tipus_contenidors as tco', 'o.tipus_contenidor_id', '=', 'tco.id')
            ->where('o.id', $offerId)
            ->select([
                'o.*',
                'c.nom_empresa as client',
                'tt.tipus as tipus_transport',
                'tf.tipus as tipus_fluxe',
                'tc.tipus as tipus_carrega',
                'ti.codi as incoterm_codi',
                'ti.nom as incoterm_nom',
                'tv.tipus as tipus_validacio',
                'eo.estat as estat',
                'tr.nom as transportista',
                'ltm.nom as linia_transport_maritim',
                'po.nom as port_origen',
                'pd.nom as port_desti',
                'ao.codi as aeroport_origen_codi',
                'ao.nom as aeroport_origen_nom',
                'ad.codi as aeroport_desti_codi',
                'ad.nom as aeroport_desti_nom',
                'tco.tipus as tipus_contenidor',
                'op.nom as operador_nom',
                'op.cognoms as operador_cognoms',
                'ac.nom as agent_nom',
                'ac.cognoms as agent_cognoms',
            ])
            ->first();

        if (! $offer) {
            return [];
        }

        $incotermCode = trim((string) ($offer->incoterm_codi ?? ''));
        $incotermName = trim((string) ($offer->incoterm_nom ?? ''));

        $incotermLabel = trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);

        return [
            'id' => $offer->id,
            'tipus_transport_id' => $offer->tipus_transport_id,
            'tipus_fluxe_id' => $offer->tipus_fluxe_id,
            'tipus_carrega_id' => $offer->tipus_carrega_id,
            'tipus_incoterm_id' => $offer->tipus_incoterm_id,
            'client_id' => $offer->client_id,
            'agent_comercial_id' => $offer->agent_comercial_id,
            'operador_id' => $offer->operador_id,
            'estat_oferta_id' => $offer->estat_oferta_id,
            'tipus_validacio_id' => $offer->tipus_validacio_id,
            'transportista_id' => $offer->transportista_id,
            'linia_transport_maritim_id' => $offer->linia_transport_maritim_id,
            'port_origen_id' => $offer->port_origen_id,
            'port_desti_id' => $offer->port_desti_id,
            'aeroport_origen_id' => $offer->aeroport_origen_id,
            'aeroport_desti_id' => $offer->aeroport_desti_id,
            'tipus_contenidor_id' => $offer->tipus_contenidor_id,
            'pes_brut' => $offer->pes_brut,
            'volum' => $offer->volum,
            'preu' => $offer->preu,
            'comentaris' => $offer->comentaris,
            'rao_rebuig' => $offer->rao_rebuig,
            'data_creacio' => $offer->data_creacio,
            'data_validessa_inicial' => $offer->data_validessa_inicial,
            'data_validessa_final' => $offer->data_validessa_final,
            'client' => $offer->client,
            'operador' => trim((string) ($offer->operador_nom ?? '') . ' ' . (string) ($offer->operador_cognoms ?? '')),
            'agent_comercial' => trim((string) ($offer->agent_nom ?? '') . ' ' . (string) ($offer->agent_cognoms ?? '')),
            'tipus_transport' => $offer->tipus_transport,
            'tipus_fluxe' => $offer->tipus_fluxe,
            'tipus_carrega' => $offer->tipus_carrega,
            'tipus_incoterm' => $incotermLabel,
            'tipus_validacio' => $offer->tipus_validacio,
            'estat' => $offer->estat,
            'transportista' => $offer->transportista,
            'linia_transport_maritim' => $offer->linia_transport_maritim,
            'port_origen' => $offer->port_origen,
            'port_desti' => $offer->port_desti,
            'aeroport_origen' => trim((string) ($offer->aeroport_origen_codi ?? '') . ' ' . (string) ($offer->aeroport_origen_nom ?? '')),
            'aeroport_desti' => trim((string) ($offer->aeroport_desti_codi ?? '') . ' ' . (string) ($offer->aeroport_desti_nom ?? '')),
            'tipus_contenidor' => $offer->tipus_contenidor,
        ];
    }

    private function canAccessOffer(Request $request, Oferta $offer): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($this->isAdminUser($user)) {
            return true;
        }

        return (int) $offer->operador_id === (int) $user->id;
    }

    private function isAdminUser($user): bool
    {
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        return (int) $user->id === 1
            || (int) $user->rol_id === 1
            || str_contains($roleName, 'admin');
    }
}
