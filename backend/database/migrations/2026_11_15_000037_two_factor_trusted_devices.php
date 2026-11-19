<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Appareils de confiance : après un code à 6 chiffres réussi, le navigateur n'en redemande plus jusqu'au renouvellement annuel. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_trusted_devices');
    }
};
