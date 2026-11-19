<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Association des étudiants et stagiaires tchadiens, une par consulat.
 * Le consulat la reconnaît et tient son registre (bureau, mandats, membres) : elle ne gère rien elle-même.
 * Seuls le nom, la description, le bureau et les contacts sont publics ; la liste des membres ne l'est pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('acronym', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_recognized')->default(false);
            $table->date('recognized_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('association_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->string('full_name', 120);
            $table->string('school', 160)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 32)->nullable();
            $table->date('mandate_start')->nullable();
            $table->date('mandate_end')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('association_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('category', 20)->default('student');
            $table->string('school', 160)->nullable();
            $table->string('field_of_study', 160)->nullable();
            $table->string('level', 60)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 32)->nullable();
            $table->date('joined_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['association_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_members');
        Schema::dropIfExists('association_officers');
        Schema::dropIfExists('associations');
    }
};
