<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gouvernance de l'association : double contrôle des pièces, date de la dernière clôture de bureau,
 * commission électorale, attestations du résultat, élections partielles pour les postes vacants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_candidates', function (Blueprint $table) {
            $table->timestamp('document_second_checked_at')->nullable()->after('document_checked_by');
            $table->foreignId('document_second_checked_by')->nullable()->after('document_second_checked_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('associations', function (Blueprint $table) {
            $table->timestamp('last_board_closed_at')->nullable();
        });

        Schema::table('association_elections', function (Blueprint $table) {
            $table->boolean('is_partial')->default(false);
            $table->json('scope_roles')->nullable(); // null = toutes les fonctions
        });

        Schema::create('association_commission_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['association_id', 'citizen_id']);
        });

        Schema::create('association_election_attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->char('chain_head', 64);
            $table->timestamp('signed_at');
            $table->unique(['election_id', 'citizen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_election_attestations');
        Schema::dropIfExists('association_commission_members');
        Schema::table('association_elections', fn (Blueprint $t) => $t->dropColumn(['is_partial', 'scope_roles']));
        Schema::table('associations', fn (Blueprint $t) => $t->dropColumn('last_board_closed_at'));
        Schema::table('association_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_second_checked_by');
            $table->dropColumn('document_second_checked_at');
        });
    }
};
