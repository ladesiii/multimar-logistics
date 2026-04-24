<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
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
            'email' => $this->correu,
            'name' => trim((string) $this->nom . ' ' . (string) $this->cognoms),
            'nom' => $this->nom,
            'cognoms' => $this->cognoms,
            'rol_id' => $this->rol_id,
            'rol' => $this->rol?->rol,
        ];
    }
}
