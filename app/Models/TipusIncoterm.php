<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusIncoterm extends Model
{
    use HasFactory;

    protected $table = 'tipus_incoterms';

    public $timestamps = false;

    // Modelo catálogo: solo representa los incoterms disponibles.
}
