<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Date d'expiration des pièces, validité d'un document délivré (carte, visa, passeport) et cas particulier signalé par le consulat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_documents', function (Blueprint $table) {
            $table->date('expires_on')->nullable();
            $table->timestamp('expiry_reminded_at')->nullable();
        });
        Schema::table('pre_requests', function (Blueprint $table) {
            $table->string('special_case', 40)->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('validity_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pre_requests', fn (Blueprint $t) => $t->dropColumn(['special_case', 'valid_until', 'validity_reminded_at']));
        Schema::table('request_documents', fn (Blueprint $t) => $t->dropColumn(['expires_on', 'expiry_reminded_at']));
    }
};
