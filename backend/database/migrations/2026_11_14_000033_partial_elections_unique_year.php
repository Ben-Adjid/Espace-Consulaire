<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Une élection partielle partage l'année de l'élection principale : l'unicité porte sur (association, année, partielle). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_elections', function (Blueprint $table) {
            $table->index('association_id'); // la clé étrangère a besoin d'un index avant de retirer l'unique
            $table->dropUnique(['association_id', 'year']);
            $table->unique(['association_id', 'year', 'is_partial']);
        });
    }

    public function down(): void
    {
        Schema::table('association_elections', function (Blueprint $table) {
            $table->dropUnique(['association_id', 'year', 'is_partial']);
            $table->unique(['association_id', 'year']);
        });
    }
};
