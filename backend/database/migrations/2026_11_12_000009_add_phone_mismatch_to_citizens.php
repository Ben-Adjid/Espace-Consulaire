<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Signalement : le numéro de téléphone indique un autre pays que le pays de résidence déclaré. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->boolean('phone_mismatch')->default(false)->after('residence_country');
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn('phone_mismatch');
        });
    }
};
