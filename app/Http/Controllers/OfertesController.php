<?php

namespace App\Http\Controllers;
use App\Models\Aeroport;
use App\Models\Cliente;
use App\Models\EstatOferta;
use App\Models\LiniaTransportMaritim;
use App\Models\Oferta;
use App\Models\Port;
use App\Models\TipusCarrega;
use App\Models\TipusContenidor;
use App\Models\TipusFluxe;
use App\Models\TipusIncoterm;
use App\Models\TipusTransport;
use App\Models\TipusValidacio;
use App\Models\Transportista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OfertesController extends Controller
{
    // Devuelve todos los catalogos necesarios para pintar los selects del formulario de oferta.
    public function opcionesFormulario(): JsonResponse
    {
        $estados = $this->obtenerOpciones(EstatOferta::class, ['estat', 'nom', 'tipus']);

        return response()->json([
            'tipus_transports' => $this->obtenerOpciones(TipusTransport::class, ['tipus', 'nom', 'codi']),
            'tipus_fluxes' => $this->obtenerOpciones(TipusFluxe::class, ['tipus', 'nom', 'codi']),
            'tipus_carrega' => $this->obtenerOpciones(TipusCarrega::class, ['tipus', 'nom', 'codi']),
            'tipus_incoterms' => $this->obtenerOpciones(TipusIncoterm::class, ['codi', 'nom']),
            'tipus_validacions' => $this->obtenerOpciones(TipusValidacio::class, ['tipus', 'nom', 'codi']),
            'estats_ofertes' => $estados,
            'clients' => $this->obtenerOpciones(Cliente::class, ['nom_empresa', 'cif_nif']),
            'transportistes' => $this->obtenerOpciones(Transportista::class, ['nom']),
            'linies_transport_maritim' => $this->obtenerOpciones(LiniaTransportMaritim::class, ['nom']),
            'ports' => $this->obtenerOpciones(Port::class, ['nom', 'codi']),
            'aeroports' => $this->obtenerOpciones(Aeroport::class, ['codi', 'nom']),
            'tipus_contenidors' => $this->obtenerOpciones(TipusContenidor::class, ['tipus', 'nom', 'codi']),
            'status_defaults' => [
                'pending_id' => 1,
                'rejected_id' => 3,
            ],
        ]);
    }

    // Lista ofertas segun rol: admin ve todo, operador solo las suyas, cliente solo las de su empresa.
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $consultaOfertas = Oferta::with($this->relacionesOferta())
            ->orderByDesc('id');

        // Filtros de visibilidad por rol.
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

    // Crea una nueva oferta aplicando reglas y valores por defecto de estado/operador segun rol.
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Si no es admin, se fuerza el operador al usuario autenticado.
            if (! $this->esUsuarioAdmin($user) && $user) {
                $request->merge([
                    'operador_id' => $user->id,
                    'estat_oferta_id' => 1,
                ]);
            } else {
                $request->merge([
                    'estat_oferta_id' => 1,
                ]);
            }

            // Se valida todo el payload con reglas centralizadas.
            $validated = $request->validate($this->reglas());

            // Se asignan campos de forma explicita para controlar bien que entra en la oferta.
            $offer = new Oferta();
            $this->asignarCamposOferta($offer, $validated);
            $offer->save();
            $offer->load($this->relacionesOferta());

            return response()->json([
                'message' => 'Oferta creada correctamente.',
                'offer' => $this->serializarOferta($offer),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al crear la oferta.',
            ], 500);
        }
    }

    // Actualiza una oferta existente respetando permisos y rol del usuario autenticado.
    public function update(Request $request, $id): JsonResponse
    {
        $offer = $this->resolverOfertaConAcceso($request, $id, 'No tienes permisos para gestionar esta oferta.');

        if ($offer instanceof JsonResponse) {
            return $offer;
        }

        try {
            $user = $request->user();

            // Operador no admin: se evita que pueda reasignar oferta a otro operador.
            if (! $this->esUsuarioAdmin($user) && $user) {
                $request->merge([
                    'operador_id' => $user->id,
                ]);
            }

            // Reutilizamos las mismas reglas de validacion para mantener consistencia.
            $validated = $request->validate($this->reglas());

            $this->asignarCamposOferta($offer, $validated);
            $offer->save();
            $offer->load($this->relacionesOferta());

            return response()->json([
                'message' => 'Oferta actualizada correctamente.',
                'offer' => $this->serializarOferta($offer),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Error interno al actualizar la oferta.',
            ], 500);
        }
    }

    // Devuelve el detalle completo de una oferta concreta.
    public function show(Request $request, $id): JsonResponse
    {
        $offer = $this->resolverOfertaConAcceso($request, $id, 'No tienes permisos para ver esta oferta.');

        if ($offer instanceof JsonResponse) {
            return $offer;
        }

        return response()->json([
            'offer' => $this->serializarDetalleOferta($offer->id),
        ]);
    }

    // Elimina una oferta si existe y el usuario tiene permiso para esa oferta.
    public function destroy(Request $request, $id): JsonResponse
    {
        $offer = $this->resolverOfertaConAcceso($request, $id, 'No tienes permisos para eliminar esta oferta.');

        if ($offer instanceof JsonResponse) {
            return $offer;
        }

        $offer->delete();

        return response()->json([
            'message' => 'Oferta eliminada correctamente.',
        ]);
    }

    // Reglas de validacion compartidas entre crear y actualizar oferta.
    private function reglas(): array
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
            'comentaris' => ['nullable', 'string'],
            'rao_rebuig' => ['nullable', 'string'],
            'data_creacio' => ['required', 'date'],
            'data_validessa_inicial' => ['nullable', 'date'],
            'data_validessa_final' => ['nullable', 'date'],
            'preu' => ['nullable', 'numeric'],
        ];
    }

    private function serializarOferta(Oferta $offer): array
    {
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
            'tipus_incoterm' => $this->formatearIncoterm($offer->tipusIncoterm?->codi, $offer->tipusIncoterm?->nom),
        ];
    }

    private function serializarDetalleOferta(int $offerId): array
    {
        // El detalle se resuelve con relaciones Eloquent precargadas.
        $offer = Oferta::with($this->relacionesDetalleOferta())
            ->find($offerId);

        // Si la consulta no encuentra la oferta, devolvemos estructura vacia.
        if (! $offer) {
            return [];
        }

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
            'data_creacio' => optional($offer->data_creacio)->format('Y-m-d'),
            'data_validessa_inicial' => optional($offer->data_validessa_inicial)->format('Y-m-d'),
            'data_validessa_final' => optional($offer->data_validessa_final)->format('Y-m-d'),
            'client' => $offer->client?->nom_empresa,
            'operador' => trim((string) ($offer->operador?->nom ?? '') . ' ' . (string) ($offer->operador?->cognoms ?? '')),
            'agent_comercial' => trim((string) ($offer->agentComercial?->nom ?? '') . ' ' . (string) ($offer->agentComercial?->cognoms ?? '')),
            'tipus_transport' => $offer->tipusTransport?->tipus,
            'tipus_fluxe' => $offer->tipusFluxe?->tipus,
            'tipus_carrega' => $offer->tipusCarrega?->tipus,
            'tipus_incoterm' => $this->formatearIncoterm($offer->tipusIncoterm?->codi, $offer->tipusIncoterm?->nom),
            'tipus_validacio' => $offer->tipusValidacio?->tipus,
            'estat' => $offer->estatOferta?->estat,
            'transportista' => $offer->transportista?->nom,
            'linia_transport_maritim' => $offer->liniaTransportMaritim?->nom,
            'port_origen' => $offer->portOrigen?->nom,
            'port_desti' => $offer->portDesti?->nom,
            'aeroport_origen' => trim((string) ($offer->aeroportOrigen?->codi ?? '') . ' ' . (string) ($offer->aeroportOrigen?->nom ?? '')),
            'aeroport_desti' => trim((string) ($offer->aeroportDesti?->codi ?? '') . ' ' . (string) ($offer->aeroportDesti?->nom ?? '')),
            'tipus_contenidor' => $offer->tipusContenidor?->tipus,
        ];
    }

    private function relacionesDetalleOferta(): array
    {
        return [
            'client:id,nom_empresa',
            'operador:id,nom,cognoms',
            'agentComercial:id,nom,cognoms',
            'tipusTransport:id,tipus',
            'tipusFluxe:id,tipus',
            'tipusCarrega:id,tipus',
            'tipusIncoterm:id,codi,nom',
            'tipusValidacio:id,tipus',
            'estatOferta:id,estat',
            'transportista:id,nom',
            'liniaTransportMaritim:id,nom',
            'portOrigen:id,nom',
            'portDesti:id,nom',
            'aeroportOrigen:id,codi,nom',
            'aeroportDesti:id,codi,nom',
            'tipusContenidor:id,tipus',
        ];
    }

    private function relacionesOferta(): array
    {
        return [
            'client:id,nom_empresa',
            'operador:id,nom,cognoms',
            'estatOferta:id,estat',
            'tipusTransport:id,tipus',
            'tipusIncoterm:id,codi,nom',
        ];
    }

    private function resolverOfertaConAcceso(Request $request, $id, string $mensajePermiso): Oferta|JsonResponse
    {
        $offer = Oferta::find($id);

        if (! $offer) {
            return response()->json(['message' => 'Oferta no encontrada.'], 404);
        }

        if (! $this->puedeAccederOferta($request, $offer)) {
            return response()->json([
                'message' => $mensajePermiso,
            ], 403);
        }

        return $offer;
    }

    // Evalua si el usuario autenticado puede ver/editar/eliminar una oferta concreta.
    private function puedeAccederOferta(Request $request, Oferta $offer): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        // Admin: acceso total.
        if ($this->esUsuarioAdmin($user)) {
            return true;
        }

        // Operador: solo ofertas asignadas a su propio usuario.
        if ($this->esUsuarioOperador($user)) {
            return (int) $offer->operador_id === (int) $user->id;
        }

        // Cliente: solo ofertas del cliente asociado a su usuario.
        if ($this->esUsuarioCliente($user)) {
            $clientId = $this->obtenerIdClientePorUsuario($user);

            if (! $clientId) {
                return false;
            }

            return (int) $offer->client_id === (int) $clientId;
        }

        return false;
    }

    // Solo cliente y admin pueden aceptar/rechazar una oferta.
    private function puedeGestionarEstadoOferta(Request $request): bool
    {
        $user = $request->user();

        return $this->esUsuarioCliente($user) || $this->esUsuarioAdmin($user);
    }

    // Heuristica de deteccion de admin por id, rol_id o nombre de rol.
    private function esUsuarioAdmin($user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $user->id === 1
            || $this->usuarioTieneRol($user, 1, ['admin']);
    }

    // Heuristica de deteccion de operador por rol_id o nombre de rol.
    private function esUsuarioOperador($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->usuarioTieneRol($user, 2, ['operador', 'operator']);
    }

    // Heuristica de deteccion de cliente por rol_id o nombre de rol.
    private function esUsuarioCliente($user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->usuarioTieneRol($user, 3, ['client']);
    }

    private function usuarioTieneRol($user, int $roleId, array $roleKeywords): bool
    {
        if ((int) ($user->rol_id ?? 0) === $roleId) {
            return true;
        }

        $roleName = strtolower((string) ($user->rol?->rol ?? ''));

        foreach ($roleKeywords as $keyword) {
            if (str_contains($roleName, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function obtenerIdClientePorUsuario($user): ?int // Obtiene el ID del cliente asociado al usuario autenticado.
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('client');

        return $user->client?->id ? (int) $user->client->id : null;
    }

    private function obtenerOpciones(string $modelClass, array $labelColumns): array // Obtiene las opciones para un desplegable a partir de un modelo Eloquent.
    {
        $rows = $modelClass::query()
            ->orderBy('id')
            ->get();

        return $rows
            ->map(function ($row) use ($labelColumns) {
                $data = (array) $row;
                $id = (int) ($data['id'] ?? 0);
                $parts = [];

                // Construye la etiqueta con el orden de columnas preferido.
                foreach ($labelColumns as $column) {
                    $value = trim((string) ($data[$column] ?? ''));

                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }

                // Fallback: si no hay columnas preferidas con texto, toma el primer campo util.
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

    private function formatearIncoterm(?string $code, ?string $name): string
    {
        $incotermCode = trim((string) ($code ?? ''));
        $incotermName = trim((string) ($name ?? ''));

        return trim($incotermCode . ($incotermCode !== '' && $incotermName !== '' ? ' - ' : '') . $incotermName);
    }

    private function asignarCamposOferta(Oferta $offer, array $validated): void
    {
        $offer->tipus_transport_id = $validated['tipus_transport_id'];
        $offer->tipus_fluxe_id = $validated['tipus_fluxe_id'];
        $offer->tipus_carrega_id = $validated['tipus_carrega_id'];
        $offer->tipus_incoterm_id = $validated['tipus_incoterm_id'];
        $offer->client_id = $validated['client_id'];
        $offer->agent_comercial_id = $validated['agent_comercial_id'] ?? null;
        $offer->operador_id = $validated['operador_id'];
        $offer->estat_oferta_id = $validated['estat_oferta_id'];
        $offer->tipus_validacio_id = $validated['tipus_validacio_id'];
        $offer->transportista_id = $validated['transportista_id'] ?? null;
        $offer->linia_transport_maritim_id = $validated['linia_transport_maritim_id'] ?? null;
        $offer->port_origen_id = $validated['port_origen_id'] ?? null;
        $offer->port_desti_id = $validated['port_desti_id'] ?? null;
        $offer->aeroport_origen_id = $validated['aeroport_origen_id'] ?? null;
        $offer->aeroport_desti_id = $validated['aeroport_desti_id'] ?? null;
        $offer->tipus_contenidor_id = $validated['tipus_contenidor_id'] ?? null;
        $offer->pes_brut = $validated['pes_brut'] ?? null;
        $offer->volum = $validated['volum'] ?? null;
        $offer->comentaris = $validated['comentaris'] ?? null;
        $offer->rao_rebuig = $validated['rao_rebuig'] ?? null;
        $offer->data_creacio = $validated['data_creacio'];
        $offer->data_validessa_inicial = $validated['data_validessa_inicial'] ?? null;
        $offer->data_validessa_final = $validated['data_validessa_final'] ?? null;
        $offer->preu = $validated['preu'] ?? null;
    }

}
