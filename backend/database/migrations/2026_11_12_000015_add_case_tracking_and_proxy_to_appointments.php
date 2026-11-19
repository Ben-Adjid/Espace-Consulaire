<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi du dossier après le passage au guichet, et réservation pour le compte d'un tiers (procuration, mineur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('case_status', 20)->nullable()->after('queue_number');
            $table->text('case_note')->nullable()->after('case_status');
            $table->timestamp('case_updated_at')->nullable()->after('case_note');
            $table->string('on_behalf_name', 120)->nullable()->after('full_name');
            $table->string('on_behalf_relation', 30)->nullable()->after('on_behalf_name');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['case_status', 'case_note', 'case_updated_at', 'on_behalf_name', 'on_behalf_relation']);
        });
    }
};
