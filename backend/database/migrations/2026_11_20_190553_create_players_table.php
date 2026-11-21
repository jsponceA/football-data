<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id')->unique();
            // PostgreSQL no crea índice en las claves foráneas; el índice acelera "jugadores de un equipo".
            $table->foreignId('team_id')->constrained()->cascadeOnDelete()->index();
            $table->string('name');
            // Posición, fecha y nacionalidad pueden venir vacías en la API.
            $table->string('position', 50)->nullable();
            // El plan gratuito no entrega el dorsal; queda null, pero la columna existe por si otro plan lo trae.
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->date('date_of_birth')->nullable();
            // Texto libre: la API es inconsistente ("Congo DR" / "DR Congo"), no se normaliza.
            $table->string('nationality', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
