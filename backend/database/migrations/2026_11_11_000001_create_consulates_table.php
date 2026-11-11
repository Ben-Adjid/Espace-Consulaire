<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('ref_prefix', 8)->default('CONS');
            // Colonnes traduisibles (JSON {fr,en,ar})
            $table->json('name');
            $table->json('represented_country');
            $table->json('host_country');
            $table->json('address')->nullable();
            $table->json('opening_hours')->nullable();
            $table->json('consul_message')->nullable();
            $table->json('mission')->nullable();
            $table->json('hero_title')->nullable();
            $table->json('hero_subtitle')->nullable();
            // Identité visuelle
            $table->string('logo')->nullable();
            $table->string('primary_color', 9)->default('#1d4ed8');
            $table->string('secondary_color', 9)->default('#facc15');
            $table->string('accent_color', 9)->default('#dc2626');
            // Contact & localisation
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('consul_name')->nullable();
            $table->json('languages');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulates');
    }
};