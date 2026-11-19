<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pré-demande en ligne : le citoyen prépare son dossier et ses pièces avant le rendez-vous. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->json('data')->nullable();
            $table->text('staff_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['consulate_id', 'status']);
        });

        Schema::create('request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_request_id')->constrained()->cascadeOnDelete();
            $table->string('label', 160);
            $table->string('stored_path', 255);
            $table->string('original_name', 255);
            $table->string('mime', 60);
            $table->unsignedInteger('size');
            $table->string('sha256', 64);
            $table->string('status', 20)->default('pending');
            $table->string('review_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_documents');
        Schema::dropIfExists('pre_requests');
    }
};
