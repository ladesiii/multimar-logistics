<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
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
        // La contraseña se guarda hasheada automáticamente al asignarla.
        return [
            'contrasenya' => 'hashed',
        ];
    }

    // Laravel usa este campo para autenticar al usuario.
    public function getAuthPassword(): string
    {
        return $this->contrasenya;
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(Cliente::class, 'usuari_id');
    }

    public function ofertesOperador(): HasMany
    {
        return $this->hasMany(Oferta::class, 'operador_id');
    }

    public function ofertesAgentComercial(): HasMany
    {
        return $this->hasMany(Oferta::class, 'agent_comercial_id');
    }
}
