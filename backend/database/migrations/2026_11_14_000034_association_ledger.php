<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Registre financier de l'association : cotisations (saisies par le trésorier) et dépenses (proposées, puis approuvées par un autre membre du bureau). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('association_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 12); // contribution | expense
            $table->unsignedInteger('amount'); // montant entier, dans la monnaie du consulat (FCFA)
            $table->string('label', 190);
            $table->date('occurred_on');
            $table->foreignId('proposed_by')->constrained('citizens')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('citizens')->nullOnDelete();
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['association_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_ledger');
    }
};
