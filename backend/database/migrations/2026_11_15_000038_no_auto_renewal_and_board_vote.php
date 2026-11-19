<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Appareils de confiance sans échéance (super admin : aucun renouvellement automatique, révocation manuelle).
 * 2. Élection par le bureau : électorat « board » pour pourvoir un poste resté vacant après l'élection partielle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('two_factor_trusted_devices', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->change();
        });

        // Idempotent : une exécution partielle précédente ne doit pas bloquer la migration.
        if (! Schema::hasColumn('association_elections', 'electorate')) {
            Schema::table('association_elections', function (Blueprint $table) {
                $table->string('electorate', 10)->default('members')->after('is_partial'); // members | board
            });
        }
        Schema::table('association_elections', function (Blueprint $table) {
            if (collect(Schema::getIndexes('association_elections'))->contains(fn ($i) => $i['name'] === 'association_elections_association_id_year_is_partial_unique')) {
                $table->dropUnique(['association_id', 'year', 'is_partial']);
            }
            $table->unique(['association_id', 'year', 'is_partial', 'electorate'], 'ae_year_partial_electorate_uq');
        });
    }

    public function down(): void
    {
        Schema::table('association_elections', function (Blueprint $table) {
            $table->dropUnique('ae_year_partial_electorate_uq');
            $table->unique(['association_id', 'year', 'is_partial']);
            $table->dropColumn('electorate');
        });
    }
};
