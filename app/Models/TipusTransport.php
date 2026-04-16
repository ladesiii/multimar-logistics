<?php

namespace App\Models;

use App\Models\Oferta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipusTransport extends Model
{
    use HasFactory;

    protected $table = 'tipus_transports';

    public $timestamps = false;

    protected $fillable = [
        'tipus',
    ];

    // Un tipo de transporte puede reutilizarse en muchas ofertas.
    public function ofertes(): HasMany
    {
        return $this->hasMany(Oferta::class, 'tipus_transport_id');
    }
}
