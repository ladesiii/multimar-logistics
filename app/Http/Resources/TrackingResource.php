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
            'tracking_step_id' => $this->tracking_step_id,
            'fecha_creacion' => optional($this->data_creacio)->format('Y-m-d'),
        ];
    }

    private function buildTrackingRoute(Oferta $offer): string
    {
        $candidates = [
            [$offer->portOrigen?->nom,      $offer->portDesti?->nom],
            [$offer->aeroportOrigen?->codi,  $offer->aeroportDesti?->codi],
            [$offer->aeroportOrigen?->nom,   $offer->aeroportDesti?->nom],
        ];

        foreach ($candidates as [$origin, $dest]) {
            $origin = trim((string) ($origin ?? ''));
            $dest   = trim((string) ($dest ?? ''));

            if ($origin !== '' || $dest !== '') {
                return ($origin ?: '-') . ' -> ' . ($dest ?: '-');
            }
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
