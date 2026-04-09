<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfertesController extends Controller
{
    public function index(): JsonResponse
    {
        $offers = Oferta::with([
            'client:id,nom_empresa',
            'operador:id,nom,cognoms',
            'estatOferta:id,estat',
            'tipusTransport:id,tipus',
        ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Oferta $offer) => $this->serializeOffer($offer));

        return response()->json([
            'offers' => $offers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $offer = Oferta::create($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus']);

        return response()->json([
            'message' => 'Oferta creada correctamente.',
            'offer' => $this->serializeOffer($offer),
        ], 201);
    }

    public function update(Request $request, Oferta $offer): JsonResponse
    {
        $validated = $request->validate($this->rules($offer));

        $offer->update($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus']);

        return response()->json([
            'message' => 'Oferta actualizada correctamente.',
            'offer' => $this->serializeOffer($offer),
        ]);
    }

    public function destroy(Oferta $offer): JsonResponse
    {
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
        ];
    }
}
