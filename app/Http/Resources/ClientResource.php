<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuari = $this->usuari;

        return [
            'id' => $this->id,
            'usuari_id' => $this->usuari_id,
            'nom' => $usuari?->nom,
            'cognoms' => $usuari?->cognoms,
            'nom_complet' => trim((string) ($usuari?->nom ?? '') . ' ' . (string) ($usuari?->cognoms ?? '')),
            'email' => $usuari?->correu,
            'nom_empresa' => $this->nom_empresa,
            'cif_nif' => $this->cif_nif,
            'telefon' => $this->telefon,
        ];
    }
}
