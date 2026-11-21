<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            // Un equipo se guarda una sola vez aunque juegue varias ligas (p. ej. Liga y Champions).
            $table->unsignedInteger('external_id')->unique();
            $table->string('name');
            // El resto de datos son opcionales: la API no siempre los trae.
            $table->string('short_name')->nullable();
            $table->string('tla', 10)->nullable();
            $table->string('crest')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedSmallInteger('founded')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
