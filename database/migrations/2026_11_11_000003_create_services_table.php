<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->json('required_documents')->nullable(); // 1 document par ligne
            $table->string('fee')->nullable();               // montant fictif
            $table->json('processing_time')->nullable();
            $table->string('icon', 32)->default('document');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('allow_appointment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['consulate_id', 'slug']);
            $table->index(['consulate_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};