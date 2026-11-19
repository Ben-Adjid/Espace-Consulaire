<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messagerie citoyen ↔ consulat : une conversation par sujet (éventuellement liée à une pré-demande),
 * avec ses messages. Les indicateurs « non lu » sont tenus des deux côtés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('citizen_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pre_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject', 150);
            $table->string('status', 10)->default('open');
            $table->boolean('unread_by_staff')->default(false);
            $table->boolean('unread_by_citizen')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['consulate_id', 'status', 'last_message_at']);
        });

        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender', 10); // citizen | staff
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('conversations');
    }
};
