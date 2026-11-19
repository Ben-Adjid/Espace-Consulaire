<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vérification de l'email des comptes citoyens et réinitialisation du mot de passe (table dédiée). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->string('email_verification_token', 64)->nullable()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('validated_at');
        });

        Schema::create('citizen_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_password_reset_tokens');

        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn(['email_verification_token', 'email_verified_at']);
        });
    }
};
