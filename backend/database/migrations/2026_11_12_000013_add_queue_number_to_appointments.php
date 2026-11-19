<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ticket de file d'attente : numéro attribué à l'arrivée, séquentiel par consulat et par jour. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedSmallInteger('queue_number')->nullable()->after('checked_in_at');
            $table->index(['consulate_id', 'appointment_date', 'queue_number']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['consulate_id', 'appointment_date', 'queue_number']);
            $table->dropColumn('queue_number');
        });
    }
};
