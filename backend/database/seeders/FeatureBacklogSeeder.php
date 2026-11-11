<?php

namespace Database\Seeders;

use App\Models\FeatureBacklog;
use Illuminate\Database\Seeder;

/** Propositions d'amélioration. Idempotent : clé = titre. */
class FeatureBacklogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->items() as $item) {
            // Le statut n'est fixé qu'à la création : une relance ne remet jamais un élément à « idée ».
            $row = FeatureBacklog::firstOrNew(['title' => $item[0]]);
            $row->fill([
                'description' => $item[1], 'category' => $item[2], 'priority' => $item[3], 'effort' => $item[4],
            ]);
            if (! $row->exists) {
                $row->status = 'idee';
            }
            $row->save();
        }
    }

    /** [titre, description, catégorie, priorité, effort] */
    private function items(): array
    {
        return [
            ['Confidentialité de la confirmation de rendez-vous', 'La page de confirmation est accessible par référence seule et affiche nom et service. Ajouter un lien signé ou un code envoyé par email.', 'correction', 'haute', 'S'],
            ['Consentement RGPD et durée de conservation', 'Case de consentement sur les formulaires, bannière cookies, et purge automatique des rendez-vous et messages après une durée définie.', 'fonctionnalite', 'haute', 'M'],
            ['Fermetures exceptionnelles des créneaux', 'Bloquer des dates précises (jours fériés) par consulat, et signaler les rendez-vous touchés pour les reporter.', 'fonctionnalite', 'haute', 'M'],
            ['Annulation ou modification par le visiteur', 'Lien dans l\'email de confirmation pour annuler ou changer de créneau, avec respect du délai minimal.', 'fonctionnalite', 'haute', 'M'],
            ['Envoi d\'emails réels (SMTP et file d\'attente)', 'Configurer un SMTP de production et passer les envois en jobs (queue:work) pour ne pas bloquer la requête.', 'correction', 'haute', 'S'],
            ['Rappel de rendez-vous J-1', 'Email automatique la veille du rendez-vous, via le scheduler Laravel.', 'fonctionnalite', 'moyenne', 'S'],
            ['Export CSV des rendez-vous et messages', 'Bouton d\'export dans Filament, filtré selon les droits du compte.', 'fonctionnalite', 'moyenne', 'S'],
            ['Journal d\'activité des actions admin', 'Qui a créé, modifié ou supprimé quoi, et quand (spatie/laravel-activitylog).', 'fonctionnalite', 'moyenne', 'M'],
            ['Authentification à deux facteurs pour l\'admin', 'Double authentification pour les comptes consulate_admin et super_admin (Filament le prend en charge).', 'fonctionnalite', 'moyenne', 'S'],
            ['Statistiques dans le tableau de bord', 'Graphiques des rendez-vous par service et par mois, taux de remplissage des créneaux.', 'fonctionnalite', 'moyenne', 'M'],
            ['Calendrier de disponibilités sur plusieurs semaines', 'Aujourd\'hui seules les 24 premières dates sont proposées. Ajouter une navigation par semaine.', 'amelioration', 'moyenne', 'S'],
            ['Recherche dans tout le site', 'Recherche unique dans les services, FAQ et actualités du consulat.', 'fonctionnalite', 'moyenne', 'M'],
            ['Bouton « Ajouter au calendrier » (.ics)', 'Sur la page de confirmation, télécharger un fichier .ics du rendez-vous.', 'amelioration', 'basse', 'S'],
            ['Éditeur riche pour actualités et pages', 'Remplacer la zone de texte brut par un éditeur (gras, listes, liens) avec sortie sécurisée.', 'amelioration', 'basse', 'M'],
            ['Bibliothèque d\'images avec compression WebP', 'Réutiliser les images, les convertir et les redimensionner automatiquement à l\'upload.', 'amelioration', 'basse', 'M'],
            ['Mode PWA pour connexions lentes', 'Mise en cache hors ligne des pages principales, installable sur mobile.', 'amelioration', 'basse', 'M'],
            ['Limiteur de débit unifié', 'Le limiteur « booking » est déclaré sans être utilisé ; BookingForm applique sa propre clé. À unifier.', 'correction', 'basse', 'S'],
            ['Fuseau horaire explicite', 'Fixer APP_TIMEZONE sur Africa/Dakar (actuellement UTC) pour la cohérence des créneaux.', 'correction', 'basse', 'S'],
            ['Cache des pages légales', 'Les pages légales sont interrogées à chaque affichage du layout. Les mettre en cache.', 'amelioration', 'basse', 'S'],
            ['Tests de bout en bout dans le navigateur', 'Playwright ou Dusk pour le parcours complet : réservation, admin, bascule AR/RTL.', 'amelioration', 'basse', 'M'],
            ['V2 — Pré-demande en ligne avec upload de documents', 'Formulaire de pré-demande, stockage privé des pièces, antivirus à l\'upload.', 'v2', 'basse', 'L'],
            ['V2 — Suivi de dossier', 'Statuts de dossier et page de suivi par référence.', 'v2', 'basse', 'M'],
            ['V2 — Paiements mobile money', 'Intégration d\'un agrégateur, en mode test d\'abord, avec webhooks signés.', 'v2', 'basse', 'L'],
            ['V2 — Notifications SMS et WhatsApp', 'Canal de notification au-delà de l\'email.', 'v2', 'basse', 'M'],
            ['V2 — Immatriculation consulaire', 'Formulaire d\'état civil et validation par un agent.', 'v2', 'basse', 'L'],
            ['V2 — Alertes d\'urgence', 'Bandeau global piloté par le super_admin et diffusion par SMS.', 'v2', 'basse', 'M'],
        ];
    }
}
