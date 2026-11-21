<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pivote: una liga tiene muchos equipos y un equipo puede estar en varias ligas
        Schema::create('competition_team', function (Blueprint $table) {
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            // la clave compuesta evita repetir el mismo par
            $table->primary(['competition_id', 'team_id']);
            // para buscar las ligas de un equipo
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_team');
    }
};
