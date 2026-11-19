<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalements des citoyens (accueil, délais, information, site, intégrité).
 * La catégorie « intégrité » (fraude, corruption) n'est visible que par le super admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('citizen_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20);
            $table->string('subject', 160);
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consulate_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_reports');
    }
};
