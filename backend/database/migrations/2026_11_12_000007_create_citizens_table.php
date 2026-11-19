<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes citoyens. Un compte est rattaché à un seul consulat (celui de son pays de résidence),
 * et reste inactif tant que ce consulat n'a pas validé sa résidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('email', 160)->unique();
            $table->string('phone', 32);
            $table->string('password');
            $table->string('nationality', 80);
            $table->string('residence_country', 80);
            $table->string('status', 20)->default('pending')->index();
            $table->text('residence_proof')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['consulate_id', 'status']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('citizen_id')->nullable()->after('consulate_id')->constrained('citizens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('citizen_id');
        });

        Schema::dropIfExists('citizens');
    }
};
