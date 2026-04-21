<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipusContenidor extends Model
{
    use HasFactory;

    protected $table = 'tipus_contenidors';

    public $timestamps = false;
}
