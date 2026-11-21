<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivote de la relación muchos a muchos: una liga tiene muchos equipos y un equipo juega varias ligas.
        Schema::create('competition_team', function (Blueprint $table) {
            // Si se borra la liga o el equipo, sus filas de enlace se borran solas.
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            // La clave compuesta impide enlazar dos veces el mismo equipo a la misma liga.
            $table->primary(['competition_id', 'team_id']);
            // Para la consulta inversa ("ligas de un equipo"), que la clave compuesta no cubre.
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team');
    }
};
