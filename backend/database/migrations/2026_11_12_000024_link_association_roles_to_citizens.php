<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le bureau et les membres peuvent être rattachés à un compte citoyen : son rôle associatif
 * (président, trésorier, membre…) apparaît dans son espace, et disparaît à la fin de son mandat.
 * Ajoute aussi l'adresse email en attente de confirmation lors d'un changement d'email.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('association_officers', function (Blueprint $table) {
            $table->foreignId('citizen_id')->nullable()->after('association_id')->constrained('citizens')->nullOnDelete();
        });

        Schema::table('association_members', function (Blueprint $table) {
            $table->foreignId('citizen_id')->nullable()->after('association_id')->constrained('citizens')->nullOnDelete();
        });

        Schema::table('citizens', function (Blueprint $table) {
            $table->string('pending_email', 160)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn('pending_email');
        });
        Schema::table('association_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('citizen_id');
        });
        Schema::table('association_officers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('citizen_id');
        });
    }
};
