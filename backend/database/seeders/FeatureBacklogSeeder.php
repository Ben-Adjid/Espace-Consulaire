<?php

namespace Database\Seeders;

use App\Models\FeatureBacklog;
use Illuminate\Database\Seeder;

/** Propositions d'amélioration. Idempotent : clé = titre. */
class FeatureBacklogSeeder extends Seeder
{
    public function run(): void
    {
        $completed = [
            'Confidentialité de la confirmation de rendez-vous',
            'Consentement RGPD et durée de conservation',
            'Fermetures exceptionnelles des créneaux',
            'Rappel de rendez-vous J-1',
            'Export CSV des rendez-vous',
            'Journal d’activité des actions admin',
            'Recherche dans tout le site',
            'Bouton « Ajouter au calendrier » (.ics)',
            'Limiteur de débit unifié',
            'Fuseau horaire explicite',
            'Cache des pages légales',
        ];

        foreach ($this->items() as $item) {
            // Le statut n'est fixé qu'à la création : une relance ne remet jamais un élément à « idée ».
            $row = FeatureBacklog::firstOrNew(['title' => $item[0]]);
            $row->fill([
                'description' => $item[1], 'category' => $item[2], 'priority' => $item[3], 'effort' => $item[4],
            ]);
            if (in_array($item[0], $completed, true)) {
                $row->status = 'fait';
            } elseif (! $row->exists) {
                $row->status = 'idee';
            }
            $row->save();
        }
    }

    /** [titre, description, catégorie, priorité, effort] */
    private function items(): array
    {
        return [
            ['Confidentialité de la confirmation de rendez-vous', 'Les détails, l’annulation et le calendrier exigent le jeton personnel transmis dans le fragment URL et dans un en-tête API.', 'correction', 'haute', 'S'],
            ['Consentement RGPD et durée de conservation', 'Consentement explicite sur les formulaires, horodatage et purge planifiée des rendez-vous passés et messages expirés.', 'fonctionnalite', 'haute', 'M'],
            ['Fermetures exceptionnelles des créneaux', 'Dates fermées par consulat, exclusion des disponibilités et signalement des rendez-vous concernés à la création de la fermeture.', 'fonctionnalite', 'haute', 'M'],
            ['Modification de rendez-vous en ligne', 'L’annulation par lien personnel est disponible; permettre au visiteur de changer de créneau sans recommencer une demande.', 'fonctionnalite', 'moyenne', 'M'],
            ['Envoi d\'emails réels (SMTP et file d\'attente)', 'Configurer un SMTP de production et passer les envois en jobs (queue:work) pour ne pas bloquer la requête.', 'correction', 'haute', 'S'],
            ['Rappel de rendez-vous J-1', 'Email automatique la veille du rendez-vous, via le scheduler Laravel.', 'fonctionnalite', 'moyenne', 'S'],
            ['Export CSV des rendez-vous', 'Export Filament des rendez-vous visibles selon le compte connecté; les messages de contact ne sont pas inclus.', 'fonctionnalite', 'moyenne', 'S'],
            ['Export CSV des messages de contact', 'Ajouter un export des messages de contact visible uniquement par les rôles habilités du consulat.', 'fonctionnalite', 'basse', 'S'],
            ['Journal d’activité des actions admin', 'Journal Filament des créations, modifications et suppressions de contenu, rendez-vous et comptes; seuls les noms de champs modifiés sont conservés.', 'fonctionnalite', 'moyenne', 'M'],
            ['Authentification à deux facteurs pour l\'admin', 'Ajouter une authentification TOTP avec procédure de récupération pour les comptes administrateurs; choisir et valider la solution avant déploiement.', 'fonctionnalite', 'haute', 'M'],
            ['Statistiques dans le tableau de bord', 'Le tableau de bord présente les rendez-vous par service dans les 30 prochains jours; ajouter tendances mensuelles et taux de remplissage.', 'fonctionnalite', 'moyenne', 'M'],
            ['Calendrier de disponibilités sur plusieurs semaines', 'Aujourd\'hui seules les 24 premières dates sont proposées. Ajouter une navigation par semaine.', 'amelioration', 'moyenne', 'S'],
            ['Recherche dans tout le site', 'Recherche dans les services, FAQ et actualités du consulat.', 'fonctionnalite', 'moyenne', 'M'],
            ['Bouton « Ajouter au calendrier » (.ics)', 'Téléchargement du rendez-vous au format .ics depuis sa page de confirmation protégée par jeton.', 'amelioration', 'basse', 'S'],
            ['Éditeur riche pour actualités et pages', 'Remplacer la zone de texte brut par un éditeur (gras, listes, liens) avec sortie sécurisée.', 'amelioration', 'basse', 'M'],
            ['Bibliothèque d\'images avec compression WebP', 'Réutiliser les images, les convertir et les redimensionner automatiquement à l\'upload.', 'amelioration', 'basse', 'M'],
            ['Mode PWA pour connexions lentes', 'Mise en cache hors ligne des pages principales, installable sur mobile.', 'amelioration', 'basse', 'M'],
            ['Limiteur de débit unifié', 'Le point d’entrée API de réservation utilise désormais le limiteur nommé booking défini dans AppServiceProvider.', 'correction', 'basse', 'S'],
            ['Fuseau horaire explicite', 'Le fuseau Africa/Dakar est la valeur par défaut; confirmer le fuseau de chaque consulat avant toute exploitation multi-pays.', 'correction', 'basse', 'S'],
            ['Cache des pages légales', 'Les pages légales du layout sont revalidées toutes les cinq minutes via le cache Next.js.', 'amelioration', 'basse', 'S'],
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
