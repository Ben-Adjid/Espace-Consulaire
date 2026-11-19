<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Journal des actions du bureau : qui a publié, modifié, approuvé ou exporté quoi, et quand. Lecture seule. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_board_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
            $table->string('action', 40);
            $table->string('label', 255);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['association_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_board_logs');
    }
};
