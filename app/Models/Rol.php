<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usuari;

class Rol extends Model
{
    protected $table = 'rols';

    public $timestamps = false;

    protected $fillable = [
        'rol',
    ];

    public function usuaris()
    {
        return $this->hasMany(Usuari::class, 'rol_id');
    }
}
