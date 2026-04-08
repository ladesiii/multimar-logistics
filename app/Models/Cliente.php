<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clients';

    public $timestamps = false;

    protected $fillable = [
        'usuari_id',
        'nom_empresa',
        'cif_nif',
        'adreca',
        'contacte',
        'email',
        'telefon',
    ];

    public function usuari(): BelongsTo
    {
        return $this->belongsTo(Usuari::class, 'usuari_id');
    }
}