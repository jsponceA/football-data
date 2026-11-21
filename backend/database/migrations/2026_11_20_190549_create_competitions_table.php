<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            // id de la liga en football-data.org: es el que usan las rutas y el que evita duplicados al importar.
            $table->unsignedInteger('external_id')->unique();
            $table->string('code', 10);
            $table->string('name');
            $table->string('type', 30);
            $table->string('emblem')->nullable();
            // TIER_ONE = gratis; las demás (p. ej. TIER_FOUR) no se pueden importar con el token gratuito.
            $table->string('plan', 20);
            $table->string('area_name');
            // Última vez que se refrescó la lista de ligas y última vez que se importaron sus equipos.
            // Con estas dos fechas se decide si el dato es "reciente" (menos de 24 h) o hay que volver a pedirlo.
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
