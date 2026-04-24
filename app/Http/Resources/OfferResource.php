<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            'operador_id' => $this->operador_id,
            'estat_oferta_id' => $this->estat_oferta_id,
            'tracking_step_id' => $this->tracking_step_id,
            'tipus_validacio_id' => $this->tipus_validacio_id,
            'data_creacio' => optional($this->data_creacio)->format('Y-m-d'),
            'preu' => $this->preu,
            'client' => $this->client?->nom_empresa,
            'operador' => trim((string) ($this->operador?->nom ?? '') . ' ' . (string) ($this->operador?->cognoms ?? '')),
            'estat' => $this->estatOferta?->estat,
            'tipus_transport' => $this->tipusTransport?->tipus,
            'tipus_incoterm' => $this->formatIncoterm($this->tipusIncoterm?->codi, $this->tipusIncoterm?->nom),
        ];
    }

    private function formatIncoterm(?string $code, ?string $name): string
    {
        $incotermCode = trim((string) ($code ?? ''));
        $incotermName = trim((string) ($name ?? ''));

        return trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);
    }
}
