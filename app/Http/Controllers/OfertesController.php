<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use App\Models\TrackingStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OfertesController extends Controller
{
    public function opcionesFormulario(): JsonResponse
    {
        $estados = $this->obtenerOpciones('estats_ofertes', ['estat', 'nom', 'tipus']);

        return response()->json([
            'tipus_transports' => $this->obtenerOpciones('tipus_transports', ['tipus', 'nom', 'codi']),
            'tipus_fluxes' => $this->obtenerOpciones('tipus_fluxes', ['tipus', 'nom', 'codi']),
            'tipus_carrega' => $this->obtenerOpciones('tipus_carrega', ['tipus', 'nom', 'codi']),
            'tipus_incoterms' => $this->obtenerOpciones('tipus_incoterms', ['codi', 'nom']),
            'tipus_validacions' => $this->obtenerOpciones('tipus_validacions', ['tipus', 'nom', 'codi']),
            'estats_ofertes' => $estados,
            'clients' => $this->obtenerOpciones('clients', ['nom_empresa', 'cif_nif']),
            'transportistes' => $this->obtenerOpciones('transportistes', ['nom']),
            'linies_transport_maritim' => $this->obtenerOpciones('linies_transport_maritim', ['nom']),
            'ports' => $this->obtenerOpciones('ports', ['nom', 'codi']),
            'aeroports' => $this->obtenerOpciones('aeroports', ['codi', 'nom']),
            'tipus_contenidors' => $this->obtenerOpciones('tipus_contenidors', ['tipus', 'nom', 'codi']),
            'status_defaults' => [
                'pending_id' => $this->buscarIdEstadoPorPalabras($estados, ['pend']),
                'rejected_id' => $this->buscarIdEstadoPorPalabras($estados, ['rebutj', 'rechaz']),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $consultaOfertas = Oferta::with([
            'client:id,nom_empresa',
            'operador:id,nom,cognoms',
            'estatOferta:id,estat',
            'tipusTransport:id,tipus',
            'tipusIncoterm:id,codi,nom',
        ])
            ->orderByDesc('id');

        if ($this->esUsuarioOperador($user)) {
            $consultaOfertas->where('operador_id', $user?->id);
        } elseif ($this->esUsuarioCliente($user)) {
            $idCliente = $this->obtenerIdClientePorUsuario($user);

            if (! $idCliente) {
                return response()->json([
                    'offers' => [],
                ]);
            }

            $consultaOfertas->where('client_id', $idCliente);
        }

        $offers = $consultaOfertas
            ->get()
            ->map(fn (Oferta $offer) => $this->serializarOferta($offer));

        return response()->json([
            'offers' => $offers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $idEstadoPendiente = $this->resolverIdEstadoPendiente();

        if (! $idEstadoPendiente) {
            return response()->json([
                'message' => 'No se pudo determinar el estado Pendiente para crear la oferta.',
            ], 422);
        }

        if (! $this->esUsuarioAdmin($user) && $user) {
            $request->merge([
                'operador_id' => $user->id,
                'estat_oferta_id' => $idEstadoPendiente,
            ]);
        } else {
            $request->merge([
                'estat_oferta_id' => $idEstadoPendiente,
            ]);
        }

        $validated = $request->validate($this->reglas());

        $offer = Oferta::create($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus', 'tipusIncoterm:id,codi,nom']);

        return response()->json([
            'message' => 'Oferta creada correctamente.',
            'offer' => $this->serializarOferta($offer),
        ], 201);
    }

    public function update(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->puedeAccederOferta($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        $user = $request->user();

        if (! $this->esUsuarioAdmin($user) && $user) {
            $request->merge([
                'operador_id' => $user->id,
            ]);
        }

        $validated = $request->validate($this->reglas($offer));

        $offer->update($validated);
        $offer->load(['client:id,nom_empresa', 'operador:id,nom,cognoms', 'estatOferta:id,estat', 'tipusTransport:id,tipus', 'tipusIncoterm:id,codi,nom']);

        return response()->json([
            'message' => 'Oferta actualizada correctamente.',
            'offer' => $this->serializarOferta($offer),
        ]);
    }

    public function show(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->puedeAccederOferta($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para ver esta oferta.',
            ], 403);
        }

        return response()->json([
            'offer' => $this->serializarDetalleOferta($offer->id),
        ]);
    }

    public function actualizarEstado(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->puedeAccederOferta($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para gestionar esta oferta.',
            ], 403);
        }

        if (! $this->puedeGestionarEstadoOferta($request)) {
            return response()->json([
                'message' => 'Solo el cliente puede aceptar o rechazar ofertas.',
            ], 403);
        }

        $validated = $request->validate([
            'estat_oferta_id' => ['required', 'integer', Rule::in([2, 3])],
            'rao_rebuig' => ['nullable', 'string', 'max:255', 'required_if:estat_oferta_id,3'],
        ]);

        if ((int) $offer->estat_oferta_id !== 1) {
            return response()->json([
                'message' => 'Esta oferta ya fue gestionada y no puede cambiar de estado.',
            ], 422);
        }

        $offer->estat_oferta_id = (int) $validated['estat_oferta_id'];
        $existeColumnaPasoTracking = $this->soportaColumnaPasoTracking();

        if ((int) $validated['estat_oferta_id'] === 3) {
            $offer->rao_rebuig = trim((string) ($validated['rao_rebuig'] ?? ''));

            if ($existeColumnaPasoTracking) {
                $offer->tracking_step_id = null;
            }
        }

        if ((int) $validated['estat_oferta_id'] === 2) {
            $offer->rao_rebuig = null;

            if ($existeColumnaPasoTracking) {
                $offer->tracking_step_id = $this->resolverIdPasoTrackingInicial();
            }
        }

        $offer->save();

        return response()->json([
            'message' => (int) $validated['estat_oferta_id'] === 2
                ? 'Oferta aceptada correctamente.'
                : 'Oferta rechazada correctamente.',
            'offer' => $this->serializarDetalleOferta($offer->id),
        ]);
    }

    public function destroy(Request $request, Oferta $offer): JsonResponse
    {
        if (! $this->puedeAccederOferta($request, $offer)) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar esta oferta.',
            ], 403);
        }

        $offer->delete();

        return response()->json([
            'message' => 'Oferta eliminada correctamente.',
        ]);
    }

    public function listarTracking(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'tracking' => [],
            ]);
        }

        // Esta consulta junta varias tablas para generar el tracking en una sola respuesta.
        $query = DB::table('ofertes as o')
            ->leftJoin('tipus_transports as tt', 'o.tipus_transport_id', '=', 'tt.id')
            ->leftJoin('tipus_incoterms as ti', 'o.tipus_incoterm_id', '=', 'ti.id')
            ->leftJoin('ports as po', 'o.port_origen_id', '=', 'po.id')
            ->leftJoin('ports as pd', 'o.port_desti_id', '=', 'pd.id')
            ->leftJoin('aeroports as ao', 'o.aeroport_origen_id', '=', 'ao.id')
            ->leftJoin('aeroports as ad', 'o.aeroport_desti_id', '=', 'ad.id')
            ->where('o.estat_oferta_id', 2)
            ->orderByDesc('o.id');

        $tieneColumnaPasoTracking = $this->soportaColumnaPasoTracking();

        if ($tieneColumnaPasoTracking) {
            $query->leftJoin('tracking_steps as ts', 'o.tracking_step_id', '=', 'ts.id');
        }

        if ($this->esUsuarioOperador($user)) {
            $query->where('o.operador_id', $user->id);
        } elseif ($this->esUsuarioCliente($user)) {
            $clientId = $this->obtenerIdClientePorUsuario($user);

            if (! $clientId) {
                return response()->json([
                    'tracking' => [],
                ]);
            }

            $query->where('o.client_id', $clientId);
        }

        $selectColumns = [
            'o.id',
            'o.data_creacio',
            'tt.tipus as medi',
            'ti.codi as incoterm_codi',
            'ti.nom as incoterm_nom',
            'po.nom as port_origen',
            'pd.nom as port_desti',
            'ao.codi as aeroport_origen_codi',
            'ao.nom as aeroport_origen_nom',
            'ad.codi as aeroport_desti_codi',
            'ad.nom as aeroport_desti_nom',
        ];

        if ($tieneColumnaPasoTracking) {
            $selectColumns[] = DB::raw('COALESCE(ts.nom, \'\') as tracking_step_nom');
        }

        $rows = $query->get($selectColumns);

        $trackingState = $this->resolverNombrePasoTrackingInicial();

        $tracking = $rows->map(function ($row) use ($trackingState) {
            $incotermCode = trim((string) ($row->incoterm_codi ?? ''));
            $incotermName = trim((string) ($row->incoterm_nom ?? ''));
            $incoterm = trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);

            return [
                'id' => (int) $row->id,
                'ruta' => $this->construirRutaTracking($row),
                'medio' => trim((string) ($row->medi ?? '')),
                'incoterm' => $incoterm,
                'estado' => trim((string) ($row->tracking_step_nom ?? '')) !== ''
                    ? trim((string) $row->tracking_step_nom)
                    : $trackingState,
                'fecha_creacion' => $row->data_creacio,
            ];
        })->values();

        return response()->json([
            'tracking' => $tracking,
        ]);
    }

    private function reglas(?Oferta $offer = null): array
    {
        return [
            'tipus_transport_id' => ['required', 'integer', Rule::exists('tipus_transports', 'id')],
            'tipus_fluxe_id' => ['required', 'integer', Rule::exists('tipus_fluxes', 'id')],
            'tipus_carrega_id' => ['required', 'integer', Rule::exists('tipus_carrega', 'id')],
            'tipus_incoterm_id' => ['required', 'integer', Rule::exists('tipus_incoterms', 'id')],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'agent_comercial_id' => ['nullable', 'integer', Rule::exists('usuaris', 'id')],
            'operador_id' => ['required', 'integer', Rule::exists('usuaris', 'id')],
            'estat_oferta_id' => ['required', 'integer', Rule::exists('estats_ofertes', 'id')],
            'tipus_validacio_id' => ['required', 'integer', Rule::exists('tipus_validacions', 'id')],
            'transportista_id' => ['nullable', 'integer', Rule::exists('transportistes', 'id')],
            'linia_transport_maritim_id' => ['nullable', 'integer', Rule::exists('linies_transport_maritim', 'id')],
            'port_origen_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'port_desti_id' => ['nullable', 'integer', Rule::exists('ports', 'id')],
            'aeroport_origen_id' => ['nullable', 'integer', Rule::exists('aeroports', 'id')],
            'aeroport_desti_id' => ['nullable', 'integer', Rule::exists('aeroports', 'id')],
            'tipus_contenidor_id' => ['nullable', 'integer', Rule::exists('tipus_contenidors', 'id')],
            'pes_brut' => ['nullable', 'numeric'],
            'volum' => ['nullable', 'numeric'],
            'comentaris' => ['nullable', 'string', 'max:255'],
            'rao_rebuig' => ['nullable', 'string', 'max:255'],
            'data_creacio' => ['required', 'date'],
            'data_validessa_inicial' => ['nullable', 'date'],
            'data_validessa_final' => ['nullable', 'date', 'after_or_equal:data_validessa_inicial'],
            'preu' => ['nullable', 'integer'],
        ];
    }

    private function serializarOferta(Oferta $offer): array
    {
        $incotermCode = trim((string) ($offer->tipusIncoterm?->codi ?? ''));
        $incotermName = trim((string) ($offer->tipusIncoterm?->nom ?? ''));
        $incotermLabel = trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);

        return [
            'id' => $offer->id,
            'tipus_transport_id' => $offer->tipus_transport_id,
            'tipus_fluxe_id' => $offer->tipus_fluxe_id,
            'tipus_carrega_id' => $offer->tipus_carrega_id,
            'tipus_incoterm_id' => $offer->tipus_incoterm_id,
            'client_id' => $offer->client_id,
            'operador_id' => $offer->operador_id,
            'estat_oferta_id' => $offer->estat_oferta_id,
            'tracking_step_id' => $offer->tracking_step_id,
            'tipus_validacio_id' => $offer->tipus_validacio_id,
            'data_creacio' => optional($offer->data_creacio)->format('Y-m-d'),
            'preu' => $offer->preu,
            'client' => $offer->client?->nom_empresa,
            'operador' => trim(($offer->operador?->nom ?? '') . ' ' . ($offer->operador?->cognoms ?? '')),
            'estat' => $offer->estatOferta?->estat,
            'tipus_transport' => $offer->tipusTransport?->tipus,
            'tipus_incoterm' => $incotermLabel,
        ];
    }

    private function serializarDetalleOferta(int $offerId): array
    {
        // El detalle usa muchos joins para reconstruir la oferta con todos sus catálogos.
        $offer = DB::table('ofertes as o')
            ->leftJoin('clients as c', 'o.client_id', '=', 'c.id')
            ->leftJoin('usuaris as op', 'o.operador_id', '=', 'op.id')
            ->leftJoin('usuaris as ac', 'o.agent_comercial_id', '=', 'ac.id')
            ->leftJoin('tipus_transports as tt', 'o.tipus_transport_id', '=', 'tt.id')
            ->leftJoin('tipus_fluxes as tf', 'o.tipus_fluxe_id', '=', 'tf.id')
            ->leftJoin('tipus_carrega as tc', 'o.tipus_carrega_id', '=', 'tc.id')
            ->leftJoin('tipus_incoterms as ti', 'o.tipus_incoterm_id', '=', 'ti.id')
            ->leftJoin('tipus_validacions as tv', 'o.tipus_validacio_id', '=', 'tv.id')
            ->leftJoin('estats_ofertes as eo', 'o.estat_oferta_id', '=', 'eo.id')
            ->leftJoin('transportistes as tr', 'o.transportista_id', '=', 'tr.id')
            ->leftJoin('linies_transport_maritim as ltm', 'o.linia_transport_maritim_id', '=', 'ltm.id')
            ->leftJoin('ports as po', 'o.port_origen_id', '=', 'po.id')
            ->leftJoin('ports as pd', 'o.port_desti_id', '=', 'pd.id')
            ->leftJoin('aeroports as ao', 'o.aeroport_origen_id', '=', 'ao.id')
            ->leftJoin('aeroports as ad', 'o.aeroport_desti_id', '=', 'ad.id')
            ->leftJoin('tipus_contenidors as tco', 'o.tipus_contenidor_id', '=', 'tco.id')
            ->where('o.id', $offerId)
            ->select([
                'o.*',
                'c.nom_empresa as client',
                'tt.tipus as tipus_transport',
                'tf.tipus as tipus_fluxe',
                'tc.tipus as tipus_carrega',
                'ti.codi as incoterm_codi',
                'ti.nom as incoterm_nom',
                'tv.tipus as tipus_validacio',
                'eo.estat as estat',
                'tr.nom as transportista',
                'ltm.nom as linia_transport_maritim',
                'po.nom as port_origen',
                'pd.nom as port_desti',
                'ao.codi as aeroport_origen_codi',
                'ao.nom as aeroport_origen_nom',
                'ad.codi as aeroport_desti_codi',
                'ad.nom as aeroport_desti_nom',
                'tco.tipus as tipus_contenidor',
                'op.nom as operador_nom',
                'op.cognoms as operador_cognoms',
                'ac.nom as agent_nom',
                'ac.cognoms as agent_cognoms',
            ])
            ->first();

        if (! $offer) {
            return [];
        }

        $incotermCode = trim((string) ($offer->incoterm_codi ?? ''));
        $incotermName = trim((string) ($offer->incoterm_nom ?? ''));

        $incotermLabel = trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);

        return [
            'id' => $offer->id,
            'tipus_transport_id' => $offer->tipus_transport_id,
            'tipus_fluxe_id' => $offer->tipus_fluxe_id,
            'tipus_carrega_id' => $offer->tipus_carrega_id,
            'tipus_incoterm_id' => $offer->tipus_incoterm_id,
            'client_id' => $offer->client_id,
            'agent_comercial_id' => $offer->agent_comercial_id,
            'operador_id' => $offer->operador_id,
            'estat_oferta_id' => $offer->estat_oferta_id,
            'tipus_validacio_id' => $offer->tipus_validacio_id,
            'transportista_id' => $offer->transportista_id,
            'linia_transport_maritim_id' => $offer->linia_transport_maritim_id,
            'port_origen_id' => $offer->port_origen_id,
            'port_desti_id' => $offer->port_desti_id,
            'aeroport_origen_id' => $offer->aeroport_origen_id,
            'aeroport_desti_id' => $offer->aeroport_desti_id,
            'tipus_contenidor_id' => $offer->tipus_contenidor_id,
            'pes_brut' => $offer->pes_brut,
            'volum' => $offer->volum,
            'preu' => $offer->preu,
            'comentaris' => $offer->comentaris,
            'rao_rebuig' => $offer->rao_rebuig,
            'data_creacio' => $offer->data_creacio,
            'data_validessa_inicial' => $offer->data_validessa_inicial,
            'data_validessa_final' => $offer->data_validessa_final,
            'client' => $offer->client,
            'operador' => trim((string) ($offer->operador_nom ?? '') . ' ' . (string) ($offer->operador_cognoms ?? '')),
            'agent_comercial' => trim((string) ($offer->agent_nom ?? '') . ' ' . (string) ($offer->agent_cognoms ?? '')),
            'tipus_transport' => $offer->tipus_transport,
            'tipus_fluxe' => $offer->tipus_fluxe,
            'tipus_carrega' => $offer->tipus_carrega,
            'tipus_incoterm' => $incotermLabel,
            'tipus_validacio' => $offer->tipus_validacio,
            'estat' => $offer->estat,
            'transportista' => $offer->transportista,
            'linia_transport_maritim' => $offer->linia_transport_maritim,
            'port_origen' => $offer->port_origen,
            'port_desti' => $offer->port_desti,
            'aeroport_origen' => trim((string) ($offer->aeroport_origen_codi ?? '') . ' ' . (string) ($offer->aeroport_origen_nom ?? '')),
            'aeroport_desti' => trim((string) ($offer->aeroport_desti_codi ?? '') . ' ' . (string) ($offer->aeroport_desti_nom ?? '')),
            'tipus_contenidor' => $offer->tipus_contenidor,
        ];
    }

    private function puedeAccederOferta(Request $request, Oferta $offer): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        if ($this->esUsuarioAdmin($user)) {
            return true;
        }

        if ($this->esUsuarioOperador($user)) {
            return (int) $offer->operador_id === (int) $user->id;
        }

        if ($this->esUsuarioCliente($user)) {
            $clientId = $this->obtenerIdClientePorUsuario($user);

            if (! $clientId) {
                return false;
            }

            return (int) $offer->client_id === (int) $clientId;
        }

        return false;
    }

    private function puedeGestionarEstadoOferta(Request $request): bool
    {
        $user = $request->user();

        return $this->esUsuarioCliente($user) || $this->esUsuarioAdmin($user);
    }

    private function esUsuarioAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        return (int) $user->id === 1
            || (int) $user->rol_id === 1
            || str_contains($roleName, 'admin');
    }

    private function esUsuarioOperador($user): bool
    {
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        return (int) $user->rol_id === 2
            || str_contains($roleName, 'operador')
            || str_contains($roleName, 'operator');
    }

    private function esUsuarioCliente($user): bool
    {
        if (! $user) {
            return false;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        return (int) $user->rol_id === 3
            || str_contains($roleName, 'client');
    }

    private function obtenerIdClientePorUsuario($user): ?int // Obtiene el ID del cliente asociado al usuario autenticado.
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('client');

        return $user->client?->id ? (int) $user->client->id : null;
    }

    private function obtenerOpciones(string $table, array $labelColumns): array // Obtiene las opciones para un desplegable a partir de los datos de una tabla.
    {
        $rows = DB::table($table)
            ->orderBy('id')
            ->get();

        return $rows
            ->map(function ($row) use ($labelColumns) {
                $data = (array) $row;
                $id = (int) ($data['id'] ?? 0);
                $parts = [];

                foreach ($labelColumns as $column) {
                    $value = trim((string) ($data[$column] ?? ''));

                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }

                if ($parts === []) {
                    foreach ($data as $column => $value) {
                        if ($column === 'id') {
                            continue;
                        }

                        $text = trim((string) $value);

                        if ($text !== '') {
                            $parts[] = $text;
                            break;
                        }
                    }
                }

                return [
                    'id' => $id,
                    'label' => $parts !== [] ? implode(' - ', $parts) : ('ID ' . $id),
                ];
            })
            ->values()
            ->all();
    }

    private function buscarIdEstadoPorPalabras(array $statuses, array $keywords): ?int // Busca el ID de un estado en base a palabras clave.
    {
        foreach ($statuses as $status) {
            $label = $this->normalizarTexto((string) ($status['label'] ?? ''));

            foreach ($keywords as $keyword) {
                if (str_contains($label, $this->normalizarTexto($keyword))) {
                    return (int) ($status['id'] ?? 0);
                }
            }
        }

        return null;
    }

    private function normalizarTexto(string $value): string // Normaliza el texto para facilitar las comparaciones, eliminando acentos, diéresis, eñes y convirtiendo a minúsculas.
    {
        $normalized = strtolower(trim($value));

        return str_replace(
            ['à', 'á', 'è', 'é', 'ì', 'í', 'ò', 'ó', 'ù', 'ú', 'ü', 'ñ'],
            ['a', 'a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u', 'u', 'n'],
            $normalized
        );
    }

    private function resolverNombrePasoTrackingInicial(): string // Busca el nombre del primer paso de tracking ordenado por 'ordre' y luego por 'id', para mostrarlo como estado inicial en el tracking.
    {
        $firstStep = TrackingStep::query()
            ->orderBy('ordre')
            ->orderBy('id')
            ->value('nom');

        return trim((string) $firstStep) !== ''
            ? trim((string) $firstStep)
            : 'En tracking';
    }

    private function resolverIdPasoTrackingInicial(): ?int // Busca el ID del primer paso de tracking ordenado por 'ordre' y luego por 'id', para asignarlo a una oferta cuando se acepta.
    {
        $firstStepId = TrackingStep::query()
            ->orderBy('ordre')
            ->orderBy('id')
            ->value('id');

        return $firstStepId ? (int) $firstStepId : null;
    }

    private function construirRutaTracking($row): string // Construye una representación de la ruta de tracking a partir de los datos disponibles en el registro, priorizando puertos y aeropuertos.
    {
        $portOrigin = trim((string) ($row->port_origen ?? ''));
        $portDest = trim((string) ($row->port_desti ?? ''));

        if ($portOrigin !== '' || $portDest !== '') {
            return trim(($portOrigin !== '' ? $portOrigin : '-') . ' -> ' . ($portDest !== '' ? $portDest : '-'));
        }

        $airportOrigin = trim((string) ($row->aeroport_origen_codi ?? ''));
        $airportDest = trim((string) ($row->aeroport_desti_codi ?? ''));

        if ($airportOrigin !== '' || $airportDest !== '') {
            return trim(($airportOrigin !== '' ? $airportOrigin : '-') . ' -> ' . ($airportDest !== '' ? $airportDest : '-'));
        }

        $airportOriginName = trim((string) ($row->aeroport_origen_nom ?? ''));
        $airportDestName = trim((string) ($row->aeroport_desti_nom ?? ''));

        if ($airportOriginName !== '' || $airportDestName !== '') {
            return trim(($airportOriginName !== '' ? $airportOriginName : '-') . ' -> ' . ($airportDestName !== '' ? $airportDestName : '-'));
        }

        return '-';
    }

    private function resolverIdEstadoPendiente(): ?int // Busca el ID del estado "Pendiente" en las opciones de estados de ofertas, utilizando palabras clave para identificarlo.
    {
        $rows = DB::table('estats_ofertes')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $data = (array) $row;
            $label = $this->normalizarTexto(implode(' ', [
                (string) ($data['estat'] ?? ''),
                (string) ($data['nom'] ?? ''),
                (string) ($data['tipus'] ?? ''),
            ]));

            if (str_contains($label, 'pend')) {
                return (int) ($data['id'] ?? 0);
            }
        }

        return isset($rows[0]?->id) ? (int) $rows[0]->id : null;
    }

    private function soportaColumnaPasoTracking(): bool // Verifica si la tabla 'ofertes' tiene la columna 'tracking_step_id' para determinar si se pueden gestionar los pasos de tracking.
    {
        return Schema::hasColumn('ofertes', 'tracking_step_id');
    }
}
