<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fermetures exceptionnelles (jours fériés, travaux) par consulat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closed_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('label', 160);
            $table->timestamps();

            $table->unique(['consulate_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closed_dates');
    }
};
