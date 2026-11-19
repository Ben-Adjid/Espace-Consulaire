<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestion des dossiers (priorités 1 et 2) :
 * - attribution d'une pré-demande à un agent ;
 * - délai de traitement cible par service (détection des retards) ;
 * - historique de chaque pré-demande ;
 * - modèles de réponses du consulat ;
 * - absences des membres de l'équipe.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('pre_requests', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('reviewed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_to');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->unsignedSmallInteger('target_days')->nullable()->after('processing_time');
        });

        Schema::create('pre_request_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor', 20)->default('staff'); // citizen, staff, system
            $table->string('type', 30);                     // submitted, assigned, reviewed
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['pre_request_id', 'created_at']);
        });

        Schema::create('response_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('staff_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason', 160)->nullable();
            $table->timestamps();
            $table->index(['consulate_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_absences');
        Schema::dropIfExists('response_templates');
        Schema::dropIfExists('pre_request_events');
        Schema::table('services', fn (Blueprint $table) => $table->dropColumn('target_days'));
        Schema::table('pre_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn('assigned_at');
        });
    }
};
