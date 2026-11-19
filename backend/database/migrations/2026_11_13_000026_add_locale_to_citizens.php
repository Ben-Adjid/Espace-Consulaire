<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Langue du citoyen (fr, en, ar) : celle de son dernier usage du site, utilisée pour ses emails. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->string('locale', 5)->default('fr')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
