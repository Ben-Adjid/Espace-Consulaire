<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Un rendez-vous peut être rattaché à la pré-demande en ligne de la même démarche (même citoyen, même service). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('pre_request_id')->nullable()->after('citizen_id')
                ->constrained('pre_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pre_request_id');
        });
    }
};
