<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $implemented = [
            'Confidentialité de la confirmation de rendez-vous',
            'Consentement RGPD et durée de conservation',
            'Fermetures exceptionnelles des créneaux',
            'Rappel de rendez-vous J-1',
            'Journal d’activité des actions admin',
            'Recherche dans tout le site',
            'Bouton « Ajouter au calendrier » (.ics)',
            'Limiteur de débit unifié',
            'Fuseau horaire explicite',
            'Cache des pages légales',
        ];

        DB::table('feature_backlog')->whereIn('title', $implemented)->update(['status' => 'fait']);

        DB::table('feature_backlog')
            ->where('title', 'Export CSV des rendez-vous et messages')
            ->update([
                'title' => 'Export CSV des rendez-vous',
                'description' => 'Export Filament des rendez-vous visibles selon le compte connecté; les messages de contact ne sont pas inclus.',
                'status' => 'fait',
            ]);

        DB::table('feature_backlog')
            ->where('title', 'Annulation ou modification par le visiteur')
            ->update([
                'title' => 'Modification de rendez-vous en ligne',
                'description' => 'L’annulation par lien personnel est disponible; permettre au visiteur de changer de créneau sans recommencer une demande.',
            ]);

        DB::table('feature_backlog')
            ->where('title', 'Statistiques dans le tableau de bord')
            ->update([
                'description' => 'Le tableau de bord présente les rendez-vous par service dans les 30 prochains jours; ajouter tendances mensuelles et taux de remplissage.',
            ]);
    }

    public function down(): void
    {
        DB::table('feature_backlog')
            ->where('title', 'Export CSV des rendez-vous')
            ->update(['title' => 'Export CSV des rendez-vous et messages']);
        DB::table('feature_backlog')
            ->where('title', 'Modification de rendez-vous en ligne')
            ->update(['title' => 'Annulation ou modification par le visiteur']);
    }
};
