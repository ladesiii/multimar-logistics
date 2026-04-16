<?php

namespace App\Models;

use App\Models\EstatOferta;
use App\Models\TrackingStep;
use App\Models\TipusIncoterm;
use App\Models\TipusTransport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oferta extends Model
{
    use HasFactory;

    protected $table = 'ofertes';

    public $timestamps = false;

    protected $fillable = [
        'tipus_transport_id',
        'tipus_fluxe_id',
        'tipus_carrega_id',
        'tipus_incoterm_id',
        'client_id',
        'agent_comercial_id',
        'operador_id',
        'estat_oferta_id',
        'tracking_step_id',
        'tipus_validacio_id',
        'transportista_id',
        'linia_transport_maritim_id',
        'port_origen_id',
        'port_desti_id',
        'aeroport_origen_id',
        'aeroport_desti_id',
        'tipus_contenidor_id',
        'pes_brut',
        'volum',
        'comentaris',
        'rao_rebuig',
        'data_creacio',
        'data_validessa_inicial',
        'data_validessa_final',
        'preu',
    ];

    protected function casts(): array
    {
        // Los campos de fecha y números se normalizan aquí para evitar lógica repetida.
        return [
            'data_creacio' => 'date',
            'data_validessa_inicial' => 'date',
            'data_validessa_final' => 'date',
            'tracking_step_id' => 'integer',
            'pes_brut' => 'decimal:2',
            'volum' => 'decimal:2',
            'preu' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'client_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(Usuari::class, 'operador_id');
    }

    public function agentComercial(): BelongsTo
    {
        return $this->belongsTo(Usuari::class, 'agent_comercial_id');
    }

    public function estatOferta(): BelongsTo
    {
        return $this->belongsTo(EstatOferta::class, 'estat_oferta_id');
    }

    public function trackingStep(): BelongsTo
    {
        return $this->belongsTo(TrackingStep::class, 'tracking_step_id');
    }

    public function tipusTransport(): BelongsTo
    {
        return $this->belongsTo(TipusTransport::class, 'tipus_transport_id');
    }

    public function tipusIncoterm(): BelongsTo
    {
        return $this->belongsTo(TipusIncoterm::class, 'tipus_incoterm_id');
    }
}
