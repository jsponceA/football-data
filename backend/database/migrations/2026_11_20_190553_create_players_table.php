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
            // postgres no indexa las foráneas solo, por eso el index
            $table->foreignId('team_id')->constrained()->cascadeOnDelete()->index();
            $table->string('name');
            $table->string('position', 50)->nullable();
            // el plan gratis no manda el dorsal, siempre queda null
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->date('date_of_birth')->nullable();
            // texto tal cual viene, la API no es consistente ("Congo DR" / "DR Congo")
            $table->string('nationality', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
