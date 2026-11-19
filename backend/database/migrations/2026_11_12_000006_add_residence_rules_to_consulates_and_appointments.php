<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattachement par pays de résidence : chaque consulat déclare les pays dont il s'occupe,
 * et chaque rendez-vous enregistre le pays de résidence et la déclaration de nationalité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulates', function (Blueprint $table) {
            $table->json('covered_countries')->nullable()->after('host_country');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('residence_country', 80)->nullable()->after('phone');
            $table->boolean('nationality_declared')->default(false)->after('residence_country');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['residence_country', 'nationality_declared']);
        });

        Schema::table('consulates', function (Blueprint $table) {
            $table->dropColumn('covered_countries');
        });
    }
};
