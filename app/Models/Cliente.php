<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clients';

    public $timestamps = false;

    protected $fillable = [
        'usuari_id',
        'nom_empresa',
        'cif_nif',
        'telefon',
    ];

    // Cada cliente pertenece a un usuario con sus datos personales.
    public function usuari(): BelongsTo
    {
        return $this->belongsTo(Usuari::class, 'usuari_id');
    }

    // Un cliente puede tener varias ofertas asociadas.
    public function ofertes(): HasMany
    {
        return $this->hasMany(Oferta::class, 'client_id');
    }
}
