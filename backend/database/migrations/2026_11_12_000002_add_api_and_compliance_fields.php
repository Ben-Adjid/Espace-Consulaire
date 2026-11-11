<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Champs pour l'API publique, la conformité (consentement, purge) et les rappels. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('access_token_hash', 64)->nullable()->after('reference');
            $table->timestamp('consent_at')->nullable()->after('notes');
            $table->timestamp('reminder_sent_at')->nullable()->after('confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('reminder_sent_at');
            $table->index('access_token_hash');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('consent_at')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('consent_at');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['access_token_hash']);
            $table->dropColumn(['access_token_hash', 'consent_at', 'reminder_sent_at', 'cancelled_at']);
        });
    }
};
