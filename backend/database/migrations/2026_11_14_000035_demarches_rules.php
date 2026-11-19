<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Règle de rendez-vous par service (dossier validé exigé) et priorité urgente sur une pré-demande. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('requires_validated_file')->default(false);
        });
        Schema::table('pre_requests', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('pre_requests', fn (Blueprint $t) => $t->dropColumn('is_urgent'));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('requires_validated_file'));
    }
};
