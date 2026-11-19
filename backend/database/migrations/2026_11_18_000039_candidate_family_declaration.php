<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Déclaration sur l'honneur : le candidat déclare n'avoir aucun lien familial avec le personnel du consulat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('association_candidates', function (Blueprint $table) {
            $table->timestamp('family_link_declared_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('association_candidates', fn (Blueprint $t) => $t->dropColumn('family_link_declared_at'));
    }
};
