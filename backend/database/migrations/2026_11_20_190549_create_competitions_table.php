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
            // id que tiene la liga en football-data, así no se duplica al importar
            $table->unsignedInteger('external_id')->unique();
            $table->string('code', 10);
            $table->string('name');
            $table->string('type', 30);
            $table->string('emblem')->nullable();
            // TIER_ONE es el plan gratis, las otras no se pueden importar
            $table->string('plan', 20);
            $table->string('area_name');
            // con estas dos fechas veo si los datos tienen más de 24 h
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
