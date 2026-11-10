<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Backlog de la plateforme : fonctionnalités, améliorations et corrections à planifier. Non lié à un consulat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_backlog', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160)->unique();
            $table->text('description');
            $table->string('category', 20)->index();   // fonctionnalite | amelioration | correction | v2
            $table->string('priority', 10)->index();   // haute | moyenne | basse
            $table->string('status', 20)->default('idee')->index(); // idee | a_faire | en_cours | fait
            $table->string('effort', 10)->nullable();  // S | M | L
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_backlog');
    }
};
