# Audit de la plateforme de gestion consulaire — 12 novembre 2026

Légende : **CT** corrigé et testé · **TF** testé et fonctionnel · **CNT** corrigé, non testé · **NC** non conforme / non fait · **B** bloqué.

## 1. Référentiel utilisé

Le dépôt ne contient pas de « cahier des charges » unique. Le référentiel reconstitué : `backend/PLAN.md` (architecture
initiale), `backend/docs/ROADMAP_V2.md` (modules prévus), le README, la table `feature_backlog`, puis le code lui-même.
Ces documents dataient du début du projet (« démo », modules V2 « non implémentés ») et ne décrivaient plus le produit.

## 2. Couverture des modules demandés

| Module | État | Où |
|---|---|---|
| Consulats et leurs informations | TF | `ConsulateResource`, assistant « Nouveau consulat » |
| Agents et rôles | TF | `UserResource`, rôles `super_admin` / `consulate_admin` / `staff` (Spatie), invitations |
| Citoyens et profils | TF | `CitizenResource` (validation / refus motivé), espace citoyen, profil modifiable |
| Demandes et dossiers | TF | Pré-demandes (`PreRequestResource`), suivi de dossier sur le rendez-vous (`CaseStatus`) |
| Pièces justificatives | TF | Stockage privé (`documents`), contrôle de type, empreinte SHA-256, accès journalisé |
| Rendez-vous | TF | File du jour, pointage, confirmation, clôture, annulation, replanification, `.ics` |
| Validations et rejets | CT | `ReviewPreRequest` : statut et motif contrôlés côté serveur, email au citoyen |
| Documents délivrés | TF | Étapes « Prêt à retirer » → « Retiré », email à chaque étape, reçus d'encaissement |
| Notifications | TF / CT | Emails : compte, rendez-vous, rappel J-1, étape du dossier, **décision de pré-demande (nouveau)**, rôle associatif, abonnement |
| Statistiques et rapports | TF | Tableaux de bord par rôle, tableau global, plan de charge, rapport mensuel CSV |
| Paramètres | TF | Fiche consulat (identité, langues, couleurs, horaires), services, créneaux, fermetures |
| Paiements en ligne, SMS/WhatsApp, immatriculation, alertes d'urgence | NC (hors périmètre actuel) | Réservés dans `config/features.php` |

## 3. Défauts trouvés et corrigés

| Défaut | Statut |
|---|---|
| Confirmation groupée des rendez-vous : repassait en « confirmé » des rendez-vous **annulés ou terminés**, sans confirmation ni message | CT |
| « Terminer » un rendez-vous sans confirmation ; aucune action de statut n'affichait de message de succès | CT |
| Examen d'une pré-demande : possible sur une demande déjà examinée, motif vérifié seulement après envoi, aucun message de succès, pièces affichées en texte brut, **citoyen jamais prévenu** | CT |
| Visiteur non connecté ouvrant un lien de pièce, de reçu ou d'export : **erreur 500** (route `login` inexistante) | CT |
| Plateforme présentée comme « outil d'information » (accueil, À propos FR/EN/AR, pied de page) | CT |
| README, `DEPLOIEMENT.md`, `config/features.php` et backlog décrivant une démo et des modules livrés comme absents | CT |

## 4. Confirmations et doubles envois

- Administration : suppressions (fenêtre native Filament), annulation et clôture d'un rendez-vous, confirmation groupée,
  activation d'un consulat, invitation, validation d'un compte, archivage du bureau, examen avec décision → confirmation ou formulaire.
- Site : suppression d'un membre de la famille, d'une pièce de pré-demande, annulation d'un rendez-vous → modale nommant l'élément.
- Tous les formulaires du site désactivent leur bouton pendant l'envoi ; Filament fait de même.
- Déconnexion : sans confirmation (aucune donnée n'est perdue, pratique usuelle) — choix assumé.

## 5. Tests exécutés

| Test | Résultat |
|---|---|
| Backend `php artisan test` | **189 réussis**, 562 assertions |
| Nouveaux : `ConsularWorkflowTest` (4), garde des liens protégés (`AuthFlowTest`) | réussis |
| Frontend : build de production avec contrôle de types | réussi (41 pages) |
| Navigateur, agent : connexion, liste des pré-demandes, ouverture de l'examen, lien de la pièce (PDF 200), refus sans motif bloqué, validation avec message | TF, vérifié en base (statut, agent) |
| Email au citoyen « Votre pré-demande — Validée » | TF (journal du mailer) |
| Citoyen : la pré-demande apparaît « Validée », non modifiable | TF (API) |
| Visiteur non connecté sur un lien de pièce | 302 vers la connexion (avant : 500) |

Les données de test ont été rechargées temporairement puis supprimées (`consulat:purge-demo`), fichier joint compris.

## 6. Fichiers modifiés

Backend : `app/Actions/ReviewPreRequest.php` (nouveau), `app/Mail/PreRequestDecisionMail.php` (nouveau),
`resources/views/emails/pre-request-decision.blade.php` (nouveau), `app/Filament/Admin/Resources/PreRequestResource.php`,
`app/Filament/Admin/Resources/AppointmentResource.php`, `bootstrap/app.php`, `config/features.php`,
`database/seeders/FeatureBacklogSeeder.php`, `docs/DEPLOIEMENT.md`, `tests/Feature/ConsularWorkflowTest.php` (nouveau),
`tests/Feature/AuthFlowTest.php`.

Frontend : `lib/platformText.ts`, `app/page.tsx`. Racine : `README.md`.

## 7. Problèmes restants et décisions

| Point | Statut |
|---|---|
| `MAIL_FROM_ADDRESS` encore `no-reply@consulat-demo.test` dans `backend/.env` ; SMTP non configuré | décision / configuration |
| Compte `superadmin@demo.test` à remplacer (refusé en production) | action de votre part |
| Double authentification obligatoire pour les consuls | décision |
| ESLint en panne (`npm ci`) | B |
| Revue pièce par pièce : chaque pièce est acceptée ou refusée (motif obligatoire) dans la fenêtre d'examen ; une pré-demande avec une pièce refusée ne peut pas être validée ; la validation accepte les pièces restantes ; l'email liste les pièces à remplacer ; le citoyen voit un badge par pièce et ne peut pas renvoyer sans remplacer les pièces refusées | CT (6 tests + navigateur agent et citoyen) |
| Rendu tablette de l'administration | NC (non testé) |
| Modules V2 (paiement en ligne, SMS, immatriculation, alertes) | hors périmètre |
| Rien n'est commité ni déployé | — |
