<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des encaissements consulaires (frais de passeport, légalisation…).
 * Un reçu n'est jamais modifié ni supprimé : une erreur se corrige par une annulation motivée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consular_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 40);
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payer_name', 120);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('XOF');
            $table->string('method', 30);
            $table->string('reference', 80)->nullable();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['consulate_id', 'receipt_number']);
            $table->index(['consulate_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consular_receipts');
    }
};
