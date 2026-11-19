<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règles du scrutin automatique : tours (majorité absolue puis second tour), électorat figé,
 * journal de votes chaîné, résultats enregistrés par le système, conditions par fonction.
 */
return new class extends Migration
{
    /** Migration rendue idempotente : une exécution précédente partiellement appliquée (MySQL ne annule pas les DDL) ne bloque plus. */
    public function up(): void
    {
        if (! Schema::hasColumn('association_elections', 'second_round_hours')) {
            Schema::table('association_elections', function (Blueprint $table) {
                $table->unsignedSmallInteger('second_round_hours')->default(72)->after('proclaimed_at');
                $table->unsignedTinyInteger('current_round')->default(1)->after('second_round_hours');
                $table->dateTime('electorate_frozen_at')->nullable()->after('current_round');
                $table->dateTime('processed_at')->nullable()->after('electorate_frozen_at');
            });
        }

        if (! Schema::hasColumn('association_candidates', 'qualified_round2')) {
            Schema::table('association_candidates', function (Blueprint $table) {
                $table->boolean('qualified_round2')->default(false)->after('status');
                $table->timestamp('document_checked_at')->nullable()->after('qualified_round2');
                $table->foreignId('document_checked_by')->nullable()->after('document_checked_at')
                    ->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('association_ballots', 'round')) {
            Schema::table('association_ballots', function (Blueprint $table) {
                $table->unsignedTinyInteger('round')->default(1)->after('citizen_id');
                $table->char('receipt_hash', 64)->nullable()->after('cast_at');
            });
        }
        if (! $this->hasUnique('association_ballots', ['election_id', 'citizen_id', 'round'])) {
            Schema::table('association_ballots', function (Blueprint $table) {
                // MySQL exige un index sur election_id (clé étrangère) avant de retirer l'unique composé.
                if (! $this->hasIndexOn('association_ballots', ['election_id'])) {
                    $table->index('election_id');
                }
                if ($this->hasUnique('association_ballots', ['election_id', 'citizen_id'])) {
                    $table->dropUnique(['election_id', 'citizen_id']);
                }
                $table->unique(['election_id', 'citizen_id', 'round']);
            });
        }

        if (! Schema::hasColumn('association_votes', 'round')) {
            Schema::table('association_votes', function (Blueprint $table) {
                $table->unsignedTinyInteger('round')->default(1)->after('candidate_id');
            });
        }

        if (! Schema::hasTable('association_vote_chain')) {
            Schema::create('association_vote_chain', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->unsignedInteger('seq');
            $table->unsignedTinyInteger('round');
            $table->string('candidate_ids', 255); // identifiants triés ; aucun lien vers l'électeur
            $table->char('prev_hash', 64);
            $table->char('hash', 64);
            $table->timestamp('created_at');
            $table->unique(['election_id', 'seq']);
            });
        }

        if (! Schema::hasTable('association_electorate')) {
        Schema::create('association_electorate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->unique(['election_id', 'citizen_id']);
        });
        }

        if (! Schema::hasTable('association_election_results')) {
        Schema::create('association_election_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->unsignedTinyInteger('round');
            $table->string('role', 30);
            $table->foreignId('candidate_id')->nullable()->constrained('association_candidates')->nullOnDelete();
            $table->string('status', 16); // elected | second_round | vacant
            $table->unsignedInteger('votes')->default(0);
            $table->unsignedInteger('turnout')->default(0);
            $table->timestamp('decided_at');
            $table->unique(['election_id', 'round', 'role']);
        });
        }

        if (! Schema::hasTable('association_post_rules')) {
        Schema::create('association_post_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->unsignedSmallInteger('min_membership_months')->default(0);
            $table->boolean('requires_student')->default(false);
            $table->boolean('requires_document')->default(false);
            $table->unsignedTinyInteger('max_consecutive_terms')->nullable();
            $table->timestamps();
            $table->unique(['association_id', 'role']);
        });
        }
    }

    private function hasIndexOn(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (! $index['primary'] && $index['columns'] === $columns) {
                return true;
            }
        }

        return false;
    }

    /** Vrai si un index unique couvre exactement ces colonnes (ordre indifférent). */
    private function hasUnique(string $table, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['unique'] && ! $index['primary'] && count(array_diff($index['columns'], $columns)) === 0 && count($index['columns']) === count($columns)) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        Schema::dropIfExists('association_post_rules');
        Schema::dropIfExists('association_election_results');
        Schema::dropIfExists('association_electorate');
        Schema::dropIfExists('association_vote_chain');
        Schema::table('association_votes', fn (Blueprint $t) => $t->dropColumn('round'));
        Schema::table('association_ballots', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'citizen_id', 'round']);
            $table->unique(['election_id', 'citizen_id']);
            $table->dropColumn(['round', 'receipt_hash']);
        });
        Schema::table('association_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_checked_by');
            $table->dropColumn(['qualified_round2', 'document_checked_at']);
        });
        Schema::table('association_elections', fn (Blueprint $t) => $t->dropColumn(['second_round_hours', 'current_round', 'electorate_frozen_at', 'processed_at']));
    }
};
