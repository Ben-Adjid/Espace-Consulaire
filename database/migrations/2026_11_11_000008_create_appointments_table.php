<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('time_slot_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 32)->unique();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 32);
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('locale', 5)->default('fr');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['time_slot_id', 'appointment_date', 'status'], 'appt_slot_date_status_idx');
            $table->index(['consulate_id', 'appointment_date', 'status'], 'appt_consulate_date_status_idx');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};