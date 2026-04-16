<?php

namespace App\Models;

use App\Models\Oferta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstatOferta extends Model
{
    use HasFactory;

    protected $table = 'estats_ofertes';

    public $timestamps = false;

    protected $fillable = [
        'estat',
    ];

    // Una oferta puede aparecer en este estado.
    public function ofertes(): HasMany
    {
        return $this->hasMany(Oferta::class, 'estat_oferta_id');
    }
}
