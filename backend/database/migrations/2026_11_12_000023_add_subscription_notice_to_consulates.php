<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Mémoire des rappels d'abonnement envoyés : étape (warning, grace, suspended) et échéance concernée. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulates', function (Blueprint $table) {
            $table->string('subscription_notice_stage', 20)->nullable()->after('suspended_for_nonpayment');
            $table->date('subscription_notice_until')->nullable()->after('subscription_notice_stage');
        });
    }

    public function down(): void
    {
        Schema::table('consulates', function (Blueprint $table) {
            $table->dropColumn(['subscription_notice_stage', 'subscription_notice_until']);
        });
    }
};
