<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuari_id')->constrained('usuaris');
            $table->string('nom_empresa', 100);
            $table->string('cif_nif', 20)->unique();
            $table->string('adreca', 255)->nullable();
            $table->string('contacte', 100)->nullable();
            $table->string('email', 100)->unique();
            $table->string('telefon', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};