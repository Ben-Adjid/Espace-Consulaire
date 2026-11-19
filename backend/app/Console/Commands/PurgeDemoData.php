<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Supprime toutes les données de démonstration avant la remise de la plateforme :
 * consulats et tout leur contenu, citoyens, rendez-vous, messages, journaux, comptes du personnel.
 * Seul le compte super administrateur indiqué est conservé (détaché de tout consulat).
 * Les rôles et permissions sont conservés. À lancer après une sauvegarde (consulat:backup).
 */
class PurgeDemoData extends Command
{
    protected $signature = 'consulat:purge-demo
        {--keep=superadmin@demo.test : Email du super administrateur à conserver}
        {--dry-run : Affiche ce qui serait supprimé, sans rien supprimer}
        {--force : Ne demande pas de confirmation}';

    protected $description = 'Supprime les données de démonstration et garde un seul super administrateur.';

    /** Ordre de suppression : les tables dépendantes avant leurs parents (clés étrangères). */
    private const TABLES = [
        'request_documents', 'pre_requests', 'consular_receipts', 'appointment_notes', 'appointments',
        'dependents', 'citizen_reports',
        'association_activities', 'association_archives', 'association_officers', 'association_members',
        'association_elections', 'associations',
        'citizen_password_reset_tokens', 'citizens',
        'contact_messages', 'closed_dates', 'service_fees', 'time_slots', 'services',
        'faqs', 'news', 'pages', 'consulate_payments', 'activity_logs',
    ];

    public function handle(): int
    {
        if (! \App\Support\EnvironmentGuard::allowsDestructive()) {
            $this->error(\App\Support\EnvironmentGuard::refusalMessage());

            return self::FAILURE;
        }

        $keepEmail = (string) $this->option('keep');
        $keeper = User::query()->where('email', $keepEmail)->first();

        if (! $keeper || ! $keeper->isSuperAdmin()) {
            $this->error("Le compte à conserver ({$keepEmail}) n’existe pas ou n’est pas super administrateur. Rien n’est supprimé.");

            return self::FAILURE;
        }

        $rows = [];
        foreach ([...self::TABLES, 'consulates'] as $table) {
            $rows[] = [$table, DB::table($table)->count()];
        }
        $otherUsers = User::query()->whereKeyNot($keeper->id)->pluck('email');
        $rows[] = ['users (supprimés)', $otherUsers->count()];
        $rows[] = ['personal_access_tokens', DB::table('personal_access_tokens')->count()];

        $this->info('Base : '.DB::connection()->getDatabaseName().' — compte conservé : '.$keepEmail);
        $this->table(['Table', 'Lignes à supprimer'], $rows);

        $logos = DB::table('consulates')->whereNotNull('logo')->pluck('logo')->all();
        $documents = DB::table('request_documents')->pluck('stored_path')->filter()->all();

        if ($this->option('dry-run')) {
            $this->line('Simulation : rien n’a été supprimé.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Supprimer définitivement ces données ?')) {
            $this->line('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($keeper) {
            foreach (self::TABLES as $table) {
                DB::table($table)->delete();
            }

            DB::table('personal_access_tokens')->delete();

            $others = User::query()->whereKeyNot($keeper->id)->pluck('id');
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $others)->delete();
            DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $others)->delete();
            DB::table('password_reset_tokens')->where('email', '!=', $keeper->email)->delete();
            DB::table('users')->whereIn('id', $others)->delete();

            DB::table('users')->where('id', $keeper->id)->update(['consulate_id' => null]);
            DB::table('consulates')->delete();
        });

        // Fichiers liés : logos publics et pièces privées des pré-demandes.
        foreach ($logos as $path) {
            Storage::disk('public')->delete($path);
        }
        foreach ($documents as $path) {
            Storage::disk(\App\Models\RequestDocument::DISK)->delete($path);
        }

        DB::table('sessions')->delete();
        $this->call('optimize:clear');

        $this->info('Données de démonstration supprimées. Changez maintenant l’email et le mot de passe du super administrateur.');

        return self::SUCCESS;
    }
}
