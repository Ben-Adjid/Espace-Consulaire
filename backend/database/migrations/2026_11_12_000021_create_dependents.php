<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Espace famille : personnes à charge rattachées au compte (enfants mineurs, conjoint, parents). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('relation', 30);
            $table->date('birth_date')->nullable();
            $table->timestamps();

            $table->index('citizen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependents');
    }
};
