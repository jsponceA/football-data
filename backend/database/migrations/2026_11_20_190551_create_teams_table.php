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
            // un equipo se guarda una sola vez aunque juegue en varias ligas
            $table->unsignedInteger('external_id')->unique();
            $table->string('name');
            // lo demás es opcional, la API no siempre lo manda
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
