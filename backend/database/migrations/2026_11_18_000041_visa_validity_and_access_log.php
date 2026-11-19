<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Validité minimale du passeport par service (visa), et journal de consultation des documents personnels. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedTinyInteger('min_passport_validity_months')->nullable();
        });

        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('consulate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 60);
            $table->string('subject_id', 40);
            $table->string('action', 30);
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at');
            $table->index(['consulate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('min_passport_validity_months'));
    }
};
