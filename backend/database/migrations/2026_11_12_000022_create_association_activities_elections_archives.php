<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activités de l'association, élections annuelles (renouvellement du bureau) et archives de personnalités.
 * Les cotisations sont volontairement exclues.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->string('location', 160)->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['association_id', 'starts_on']);
        });

        Schema::create('association_elections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->date('held_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['association_id', 'year']);
        });

        Schema::table('association_officers', function (Blueprint $table) {
            $table->foreignId('election_id')->nullable()->after('association_id')
                ->constrained('association_elections')->nullOnDelete();
        });

        Schema::create('association_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('person_name', 120);
            $table->string('title', 160)->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_archives');
        Schema::table('association_officers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('election_id');
        });
        Schema::dropIfExists('association_elections');
        Schema::dropIfExists('association_activities');
    }
};
