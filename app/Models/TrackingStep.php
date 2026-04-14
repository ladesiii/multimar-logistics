<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingStep extends Model
{
    use HasFactory;

    protected $table = 'tracking_steps';

    public $timestamps = false;

    protected $fillable = [
        'ordre',
        'nom',
    ];

    public function ofertes(): HasMany
    {
        return $this->hasMany(Oferta::class, 'tracking_step_id');
    }
}
