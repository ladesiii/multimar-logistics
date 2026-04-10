<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Usuari;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientesController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Cliente::with('usuari')
            ->orderBy('id')
            ->get()
            ->map(function (Cliente $client) {
                $usuari = $client->usuari;

                return [
                    'id' => $client->id,
                    'usuari_id' => $client->usuari_id,
                    'nom' => $usuari?->nom,
                    'cognoms' => $usuari?->cognoms,
                    'nom_complet' => trim(($usuari?->nom ?? '') . ' ' . ($usuari?->cognoms ?? '')),
                    'email' => $usuari?->correu,
                    'nom_empresa' => $client->nom_empresa,
                    'cif_nif' => $client->cif_nif,
                    'telefon' => $client->telefon,
                ];
            });

        return response()->json([
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'cognoms' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:100', 'unique:usuaris,correu'],
            'nom_empresa' => ['required', 'string', 'max:100'],
            'cif_nif' => ['required', 'string', 'max:20', 'unique:clients,cif_nif'],
            'telefon' => ['nullable', 'string', 'max:20'],
        ]);

        $client = DB::transaction(function () use ($validated) {
            $usuari = Usuari::create([
                'nom' => $validated['nom'],
                'cognoms' => $validated['cognoms'],
                'correu' => $validated['email'],
                'contrasenya' => $validated['password'],
                'rol_id' => 2,
            ]);

            return Cliente::create([
                'usuari_id' => $usuari->id,
                'nom_empresa' => $validated['nom_empresa'],
                'cif_nif' => $validated['cif_nif'],
                'telefon' => $validated['telefon'] ?? null,
            ]);
        });

        $client->load('usuari');

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'client' => [
                'id' => $client->id,
                'usuari_id' => $client->usuari_id,
                'nom' => $client->usuari?->nom,
                'cognoms' => $client->usuari?->cognoms,
                'nom_complet' => trim(($client->usuari?->nom ?? '') . ' ' . ($client->usuari?->cognoms ?? '')),
                'email' => $client->usuari?->correu,
                'nom_empresa' => $client->nom_empresa,
                'cif_nif' => $client->cif_nif,
                'telefon' => $client->telefon,
            ],
        ], 201);
    }

    public function update(Request $request, Cliente $client): JsonResponse
    {
        $client->load('usuari');

        if (! $client->usuari) {
            throw new ModelNotFoundException('No se encontró el usuario asociado al cliente.');
        }

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'cognoms' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('usuaris', 'correu')->ignore($client->usuari_id),
            ],
            'password' => ['nullable', 'string', 'max:255'],
            'nom_empresa' => ['required', 'string', 'max:100'],
            'cif_nif' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'cif_nif')->ignore($client->id),
            ],
            'telefon' => ['nullable', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($client, $validated) {
            $usuari = $client->usuari;
            $usuari->nom = $validated['nom'];
            $usuari->cognoms = $validated['cognoms'];
            $usuari->correu = $validated['email'];

            if (!empty($validated['password'])) {
                $usuari->contrasenya = $validated['password'];
            }

            $usuari->save();

            $client->nom_empresa = $validated['nom_empresa'];
            $client->cif_nif = $validated['cif_nif'];
            $client->telefon = $validated['telefon'] ?? null;
            $client->save();
        });

        $client->load('usuari');

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'client' => [
                'id' => $client->id,
                'usuari_id' => $client->usuari_id,
                'nom' => $client->usuari?->nom,
                'cognoms' => $client->usuari?->cognoms,
                'nom_complet' => trim(($client->usuari?->nom ?? '') . ' ' . ($client->usuari?->cognoms ?? '')),
                'email' => $client->usuari?->correu,
                'nom_empresa' => $client->nom_empresa,
                'cif_nif' => $client->cif_nif,
                'telefon' => $client->telefon,
            ],
        ]);
    }

    public function destroy(Cliente $client): JsonResponse
    {
        DB::transaction(function () use ($client) {
            $usuariId = $client->usuari_id;
            $client->delete();

            if ($usuariId) {
                Usuari::where('id', $usuariId)->delete();
            }
        });

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }
}