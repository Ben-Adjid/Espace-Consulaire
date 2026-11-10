<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            // null = créneau valable pour tous les services
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = dimanche … 6 = samedi
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['consulate_id', 'weekday', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};