<?php

namespace App\Http\Controllers;
use App\Clases\Utilitat;
use App\Http\Resources\OfferDetailResource;
use App\Http\Resources\OfferResource;
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
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            ->get();

        return response()->json([
            'offers' => OfferResource::collection($offers),
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
                'offer' => new OfferResource($offer),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al crear la oferta.',
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
                'offer' => new OfferResource($offer),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Datos de validación incorrectos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            $mensaje = Utilitat::errorMessage($e);

            return response()->json([
                'message' => !empty($mensaje) ? $mensaje : 'Error interno al actualizar la oferta.',
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

        $offer->load($this->relacionesDetalleOferta());

        return response()->json([
            'offer' => new OfferDetailResource($offer),
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
        $offer = Oferta::findOrFail($id);

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

    // Obtiene el rol del usuario autenticado (1=admin, 2=operador, 3=cliente, null=sin rol).
    private function obtenerRolUsuario($user): ?int
    {
        if (! $user) {
            return null;
        }

        $role = $user->rol_id ?? null;

        return is_null($role) ? null : (int) $role;
    }

    private function esUsuarioAdmin($user): bool
    {
        // Mantener la comprobación por id=1 como admin especial
        if (! $user) {
            return false;
        }

        return (int) ($user->id ?? 0) === 1 || $this->obtenerRolUsuario($user) === 1;
    }

    private function esUsuarioOperador($user): bool
    {
        return $this->obtenerRolUsuario($user) === 2;
    }

    private function esUsuarioCliente($user): bool
    {
        return $this->obtenerRolUsuario($user) === 3;
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
        // Crea una consulta al modelo que se le pasa por parámetro.
        return $modelClass::query()
            // Ordena los registros por ID para que salgan siempre en el mismo orden.
            ->orderBy('id')
            // Trae todos los registros de esa tabla.
            ->get()
            // Recorre cada registro y lo transforma en una opción simple para el frontend.
            ->map(function ($row) use ($labelColumns) {
                // Guarda el ID del registro como número entero.
                $id = (int) ($row->id ?? 0);
                // Aquí se construye el texto visible de la opción.
                $label = '';

                // Recorre las columnas preferidas para buscar la primera que tenga texto.
                foreach ($labelColumns as $column) {
                    // Lee el valor de esa columna y lo limpia de espacios.
                    $value = trim((string) ($row->{$column} ?? ''));

                    // Si la columna tiene contenido, se usa como etiqueta y se deja de buscar.
                    if ($value !== '') {
                        $label = $value;
                        break;
                    }
                }

                // Devuelve un array estándar con el ID y el texto que verá el frontend.
                return [
                    'id' => $id,
                    // Si no se encontró ningún texto, usa un fallback tipo "ID 5".
                    'label' => $label !== '' ? $label : ('ID ' . $id),
                ];
            })
            // Reindexa el array para que quede limpio y consecutivo.
            ->values()
            // Convierte la colección de Laravel a array normal de PHP.
            ->all();
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
