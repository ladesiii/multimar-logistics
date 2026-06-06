<?php

namespace App\Http\Controllers;

use App\Clases\Utilitat;
use App\Http\Resources\ClientResource;
use App\Models\Cliente;
use App\Models\Usuari;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientesController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Cliente::with('usuari')
            ->orderBy('id')
            ->get();

        return response()->json([
            'clients' => ClientResource::collection($clients),
        ]);
    }

    public function store(Request $request): JsonResponse // Crea un nuevo cliente junto con su usuario
    {
        try {
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
                'client' => new ClientResource($client),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al crear el cliente.',
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse // Actualiza un cliente existente junto con su usuario
    {
        $client = Cliente::findOrFail($id);

        $client->load('usuari');

        if (! $client->usuari) {
            return response()->json(['message' => 'No se encontró el usuario asociado al cliente.'], 404);
        }

        try {
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
                'client' => new ClientResource($client),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al actualizar el cliente.',
            ], 500);
        }
    }

    public function destroy($id): JsonResponse // Elimina un cliente junto con su usuario asociado
    {
        $client = Cliente::findOrFail($id);

        DB::transaction(function () use ($client) {
            $usuariId = $client->usuari_id;
            $client->delete();

            if ($usuariId) {
                Usuari::query()->where('id', '=', $usuariId)->delete();
            }
        });

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }
}
