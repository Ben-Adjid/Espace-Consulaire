<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scrutin en ligne : fenêtre de vote sur l'élection, candidatures, bulletins (qui a voté) et votes (pour qui).
 * Les bulletins et les votes sont dans deux tables séparées, sans lien entre l'électeur et son choix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_elections', function (Blueprint $table) {
            $table->dateTime('voting_opens_at')->nullable()->after('held_on');
            $table->dateTime('voting_closes_at')->nullable()->after('voting_opens_at');
            $table->dateTime('proclaimed_at')->nullable()->after('voting_closes_at');
        });

        Schema::create('association_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->timestamps();
            $table->unique(['election_id', 'citizen_id']);
        });

        Schema::create('association_ballots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->timestamp('cast_at');
            $table->unique(['election_id', 'citizen_id']);
        });

        Schema::create('association_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained('association_elections')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('association_candidates')->cascadeOnDelete();
            $table->index(['election_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_votes');
        Schema::dropIfExists('association_ballots');
        Schema::dropIfExists('association_candidates');
        Schema::table('association_elections', function (Blueprint $table) {
            $table->dropColumn(['voting_opens_at', 'voting_closes_at', 'proclaimed_at']);
        });
    }
};
