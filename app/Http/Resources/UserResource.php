<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'nom' => $this->nom,
            'cognoms' => $this->cognoms,
            'nom_complet' => trim((string) $this->nom . ' ' . (string) $this->cognoms),
            'email' => $this->correu,
            'rol_id' => $this->rol_id,
            'rol' => $this->rol?->rol ?? 'Sin rol',
        ];
    }
}
