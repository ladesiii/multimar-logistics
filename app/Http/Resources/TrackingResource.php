<?php

namespace App\Http\Resources;

use App\Models\Oferta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingResource extends JsonResource
{
    private string $defaultState = 'En tracking';

    public function withDefaultState(string $defaultState): self
    {
        $this->defaultState = $defaultState;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'ruta' => $this->buildTrackingRoute($this->resource),
            'medio' => trim((string) ($this->tipusTransport?->tipus ?? '')),
            'incoterm' => $this->formatIncoterm($this->tipusIncoterm?->codi, $this->tipusIncoterm?->nom),
            'estado' => trim((string) ($this->trackingStep?->nom ?? '')) !== ''
                ? trim((string) $this->trackingStep?->nom)
                : $this->defaultState,
            'fecha_creacion' => optional($this->data_creacio)->format('Y-m-d'),
        ];
    }

    private function buildTrackingRoute(Oferta $offer): string
    {
        $portOrigin = trim((string) ($offer->portOrigen?->nom ?? ''));
        $portDest = trim((string) ($offer->portDesti?->nom ?? ''));

        if ($portOrigin !== '' || $portDest !== '') {
            return trim(($portOrigin !== '' ? $portOrigin : '-') . ' -> ' . ($portDest !== '' ? $portDest : '-'));
        }

        $airportOrigin = trim((string) ($offer->aeroportOrigen?->codi ?? ''));
        $airportDest = trim((string) ($offer->aeroportDesti?->codi ?? ''));

        if ($airportOrigin !== '' || $airportDest !== '') {
            return trim(($airportOrigin !== '' ? $airportOrigin : '-') . ' -> ' . ($airportDest !== '' ? $airportDest : '-'));
        }

        $airportOriginName = trim((string) ($offer->aeroportOrigen?->nom ?? ''));
        $airportDestName = trim((string) ($offer->aeroportDesti?->nom ?? ''));

        if ($airportOriginName !== '' || $airportDestName !== '') {
            return trim(($airportOriginName !== '' ? $airportOriginName : '-') . ' -> ' . ($airportDestName !== '' ? $airportDestName : '-'));
        }

        return '-';
    }

    private function formatIncoterm(?string $code, ?string $name): string
    {
        $incotermCode = trim((string) ($code ?? ''));
        $incotermName = trim((string) ($name ?? ''));

        return trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);
    }
}
