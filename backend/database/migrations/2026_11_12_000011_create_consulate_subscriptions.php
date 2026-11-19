<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnement annuel des consulats à la plateforme.
 * Chaque paiement reçu est enregistré par le super admin ; il prolonge la date de fin d'abonnement.
 * Après la période de grâce sans paiement, le consulat est suspendu automatiquement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consulates', function (Blueprint $table) {
            $table->date('subscription_until')->nullable()->after('is_active');
            $table->boolean('suspended_for_nonpayment')->default(false)->after('subscription_until');
        });

        Schema::create('consulate_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 8)->default('XOF');
            $table->date('paid_at');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('method', 30);
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consulate_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulate_payments');

        Schema::table('consulates', function (Blueprint $table) {
            $table->dropColumn(['subscription_until', 'suspended_for_nonpayment']);
        });
    }
};
