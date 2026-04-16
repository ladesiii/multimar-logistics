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
    private function formatearCliente(Cliente $client): array // Función para no repetir código al formatear el cliente en index, store y update.
    {
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
    }

    public function index(): JsonResponse
    {
        $clients = Cliente::with('usuari')
            ->orderBy('id')
            ->get()
            ->map(fn (Cliente $client) => $this->formatearCliente($client)); // map() transforma el array en uno nuevo con el formato que queremos.

        return response()->json([
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): JsonResponse // Crea un nuevo cliente junto con su usuario
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
            // Crear usuario nuevo
            $usuari = new Usuari();
            $usuari->nom = $validated['nom'];
            $usuari->cognoms = $validated['cognoms'];
            $usuari->correu = $validated['email'];
            $usuari->contrasenya = $validated['password'];
            $usuari->rol_id = 3;
            $usuari->save();

            // Crear cliente nuevo
            $client = new Cliente();
            $client->usuari_id = $usuari->id;
            $client->nom_empresa = $validated['nom_empresa'];
            $client->cif_nif = $validated['cif_nif'];
            $client->telefon = $validated['telefon'] ?? null;
            $client->save();

            return $client;
        });

        $client->load('usuari');

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'client' => $this->formatearCliente($client),
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse // Actualiza un cliente existente junto con su usuario
    {
        $client = Cliente::find($id);

        if (! $client) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $client->load('usuari');

        if (! $client->usuari) {
            return response()->json(['message' => 'No se encontró el usuario asociado al cliente.'], 404);
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
            'client' => $this->formatearCliente($client),
        ]);
    }

    public function destroy($id): JsonResponse // Elimina un cliente junto con su usuario asociado
    {
        $client = Cliente::find($id);

        if (! $client) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

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
