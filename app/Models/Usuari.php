<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Rol;
use Laravel\Sanctum\HasApiTokens;

class Usuari extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'usuaris';

    public $timestamps = false;

    protected $fillable = [
        'correu',
        'contrasenya',
        'nom',
        'cognoms',
        'rol_id',
    ];

    protected $hidden = [
        'contrasenya',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'contrasenya' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasenya;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(Cliente::class, 'usuari_id');
    }
}
