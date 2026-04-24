<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipus_transport_id' => $this->tipus_transport_id,
            'tipus_fluxe_id' => $this->tipus_fluxe_id,
            'tipus_carrega_id' => $this->tipus_carrega_id,
            'tipus_incoterm_id' => $this->tipus_incoterm_id,
            'client_id' => $this->client_id,
            'agent_comercial_id' => $this->agent_comercial_id,
            'operador_id' => $this->operador_id,
            'estat_oferta_id' => $this->estat_oferta_id,
            'tipus_validacio_id' => $this->tipus_validacio_id,
            'transportista_id' => $this->transportista_id,
            'linia_transport_maritim_id' => $this->linia_transport_maritim_id,
            'port_origen_id' => $this->port_origen_id,
            'port_desti_id' => $this->port_desti_id,
            'aeroport_origen_id' => $this->aeroport_origen_id,
            'aeroport_desti_id' => $this->aeroport_desti_id,
            'tipus_contenidor_id' => $this->tipus_contenidor_id,
            'pes_brut' => $this->pes_brut,
            'volum' => $this->volum,
            'preu' => $this->preu,
            'comentaris' => $this->comentaris,
            'rao_rebuig' => $this->rao_rebuig,
            'data_creacio' => optional($this->data_creacio)->format('Y-m-d'),
            'data_validessa_inicial' => optional($this->data_validessa_inicial)->format('Y-m-d'),
            'data_validessa_final' => optional($this->data_validessa_final)->format('Y-m-d'),
            'client' => $this->client?->nom_empresa,
            'operador' => trim((string) ($this->operador?->nom ?? '') . ' ' . (string) ($this->operador?->cognoms ?? '')),
            'agent_comercial' => trim((string) ($this->agentComercial?->nom ?? '') . ' ' . (string) ($this->agentComercial?->cognoms ?? '')),
            'tipus_transport' => $this->tipusTransport?->tipus,
            'tipus_fluxe' => $this->tipusFluxe?->tipus,
            'tipus_carrega' => $this->tipusCarrega?->tipus,
            'tipus_incoterm' => $this->formatIncoterm($this->tipusIncoterm?->codi, $this->tipusIncoterm?->nom),
            'tipus_validacio' => $this->tipusValidacio?->tipus,
            'estat' => $this->estatOferta?->estat,
            'transportista' => $this->transportista?->nom,
            'linia_transport_maritim' => $this->liniaTransportMaritim?->nom,
            'port_origen' => $this->portOrigen?->nom,
            'port_desti' => $this->portDesti?->nom,
            'aeroport_origen' => trim((string) ($this->aeroportOrigen?->codi ?? '') . ' ' . (string) ($this->aeroportOrigen?->nom ?? '')),
            'aeroport_desti' => trim((string) ($this->aeroportDesti?->codi ?? '') . ' ' . (string) ($this->aeroportDesti?->nom ?? '')),
            'tipus_contenidor' => $this->tipusContenidor?->tipus,
        ];
    }

    private function formatIncoterm(?string $code, ?string $name): string
    {
        $incotermCode = trim((string) ($code ?? ''));
        $incotermName = trim((string) ($name ?? ''));

        return trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);
    }
}
