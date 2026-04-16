<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'rols';

    public $timestamps = false;

    protected $fillable = [
        'rol',
    ];

    // Un rol puede estar asignado a varios usuarios.
    public function usuaris(): HasMany
    {
        return $this->hasMany(Usuari::class, 'rol_id');
    }
}
