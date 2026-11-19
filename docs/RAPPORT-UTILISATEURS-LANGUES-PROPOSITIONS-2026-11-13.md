# Audit par utilisateur, sidebar, trois langues et propositions — 13 novembre 2026

Légende : **CT** corrigé et testé · **TF** testé et fonctionnel · **NC** non fait · **AC** à confirmer.

## 1. Inventaire des rôles et des comptes

| Rôle | Espace | Comptes de test | Périmètre |
|---|---|---|---|
| `super_admin` | `/admin` | `superadmin@demo.test` | toute la plateforme |
| `consulate_admin` (consul) | `/admin` | `admin.tchad`, `admin.tchad-cm`, `admin.tchad-gh`, `admin.tchad-tg` (@demo.test) | son consulat |
| `staff` (agent) | `/admin` | `staff.tchad`, `staff.tchad-cm`, `staff.tchad-gh`, `staff.tchad-tg` (@demo.test) | son consulat |
| Citoyen | site du consulat, `/compte` | compte de test créé pour les essais | son compte, son consulat |

Le projet ne contient pas de rôle « responsable de service » ni de rôle « citoyen membre d'association » distinct :
la fonction associative est portée par le compte citoyen (bureau relié au compte, fin de mandat automatique).

## 2. Matrice des permissions

| Permission | super_admin | consul | agent |
|---|---|---|---|
| `manage_content` (services, actualités, FAQ, pages, créneaux) | oui | oui | non (lecture) |
| `view_content` | oui | oui | oui |
| `manage_appointments` (rendez-vous, pré-demandes, encaissements, file du jour) | oui | oui | oui |
| `manage_messages` | oui | oui | oui |
| `manage_users` (équipe, comptes citoyens, journal d'activité) | oui | oui | non |
| `edit_consulate` | oui | oui (son consulat) | non |
| `delete_records` | oui | oui | non |
| `view_dashboard` | oui | oui | oui |
| Pilotage plateforme (consulats, abonnements, tableau global, nouveau consulat) | oui | non | non |

Contrôles côté serveur : `User::canAccessPanel`, `canAccess` / `canViewAny` par page et ressource, scope par consulat,
policies. Testés par URL directe (`AdminUrlAccessTest`, `PerAccountAuditTest`).

## 3. Audit compte par compte — TF

Détail généré automatiquement : [AUDIT-COMPTES.md](AUDIT-COMPTES.md) (`backend/tests/Feature/PerAccountAuditTest.php`).
Pour **chacun des 9 comptes**, dans une application neuve : connexion par le formulaire, tableau de bord du rôle,
sidebar réellement affichée, pages interdites ouvertes par URL (403/404 attendu), rendez-vous d'autres consulats
absents de la liste, déconnexion.

| Utilisateur | Rôle | Tableau de bord | Sidebar | Résultat |
|---|---|---|---|---|
| superadmin@demo.test | super_admin | plateforme | 23 rubriques | conforme |
| admin.tchad / -cm / -gh / -tg | consul | travail du jour + pilotage | 21 rubriques chacun | conformes |
| staff.tchad / -cm / -gh / -tg | agent | travail du jour | 16 rubriques chacun | conformes **après correction** |

**Anomalie trouvée et corrigée** : les 4 agents voyaient le « Journal d'activité » (historique des actions de tous
leurs collègues), faute de règle d'accès. Il est désormais réservé au super administrateur et au consul. — CT

Citoyen (parcours vérifiés précédemment dans le navigateur) : connexion, espace, rendez-vous, pré-demande avec pièces
et badges de décision, famille, signalements, profil, déconnexion, accès refusé sur un autre consulat. — TF

## 4. Sidebar — CT

Réorganisée en **cinq catégories métier**, dans l'ordre d'usage :

| Catégorie | Rubriques |
|---|---|
| Accueil du public | File du jour · Rendez-vous · Plan de charge |
| Dossiers et citoyens | Pré-demandes · Comptes citoyens · Encaissements · Signalements |
| Communication | Messages reçus · Actualités · Questions fréquentes · Pages légales · Association des étudiants |
| Paramétrage | Consulats / Mon consulat · Services · Créneaux horaires · Fermetures · Équipe et comptes · Mon abonnement |
| Pilotage | Tableau global · Rapport mensuel · Journal d'activité · Nouveau consulat · Abonnements des consulats |

Corrigé : groupe « Rendez-vous » qui mélangeait messages, signalements et encaissements ; comptes citoyens rangés
dans « Administration » ; icônes en double (Services / Pré-demandes, Consulats / Nouveau consulat) ; intitulés en
« Majuscules À Chaque Mot » ; ordres en conflit. Chaque rôle ne voit que ses rubriques (vérifié pour les 9 comptes).
Déjà en place : menu repliable sur ordinateur, fermé par défaut sur téléphone, menu actif mis en évidence,
groupes repliables, profil et déconnexion dans le menu utilisateur, recherche Ctrl+K.

## 5. Trois langues et RTL

| Zone | FR | EN | AR (RTL) | Statut |
|---|---|---|---|---|
| Administration : menus, groupes, titres, tableaux, filtres, formulaires, aides, actions, modales, notifications, widgets, pages Blade | oui | oui | oui | CT |
| Textes propres à Filament (Créer, Rechercher, Filtres, pagination, validations) | oui | oui | oui | TF (traductions fournies par Filament) |
| Connexion d'administration (+ sélecteur de langue) | oui | oui | oui | CT |
| Plateforme : accueil, À propos, Crédits, Connexion | oui | oui | oui | CT |
| Sites des consulats et espace citoyen | oui | oui | oui | TF |
| Emails (11 modèles : compte, lien, décision, pré-demande, rendez-vous, rappel, étape du dossier, fermeture, association, invitation, abonnement) | oui | oui | oui | CT (`EmailLocaleTest`, 5 tests) |

- Sélecteur **FR · EN · العربية** dans la barre supérieure de l'administration et sur l'écran de connexion.
- Choix **enregistré pour l'utilisateur** (colonne `users.locale`) : conservé entre les pages et après reconnexion.
- Système centralisé : textes français comme clés, traductions dans `backend/lang/en.json` et `ar.json`
  (428 textes de l'administration couverts, 0 manquant à l'extraction) ; site public dans `frontend/lib/i18n.ts` et `platformText.ts`.
- Arabe : `dir="rtl"`, sidebar à droite, alignements, formulaires et modales inversés par Filament ; dates localisées.
- Tests : `AdminLocaleTest` (6) ; balayage navigateur de 12 pages d'administration en arabe et 12 en anglais : aucun texte français restant.

## 6. Autres corrections de cette étape

| Correction | Statut |
|---|---|
| Journal d'activité réservé au super administrateur et au consul | CT |
| Nom du consulat affiché dans la langue de l'utilisateur sur le tableau de bord | CT |
| Titres de widgets, compteurs et messages dynamiques traduisibles (paramètres `:count`) | CT |

## 7. Tests exécutés

| Test | Résultat |
|---|---|
| Backend complet | **210 réussis**, 686 assertions |
| `PerAccountAuditTest` (9 comptes) | réussis |
| `AdminLocaleTest` (6) | réussis |
| Frontend : build de production avec contrôle de types | réussi (43 pages) |
| Navigateur : administration en arabe (capture), 12 pages AR et 12 pages EN sans texte français | TF |
| Navigateur : accueil `/ar` en RTL, sélecteur de langue, liens dans la langue, pas de débordement | TF |

## 8. Restant

| Point | Statut |
|---|---|
| Emails dans la langue du destinataire : citoyen = langue de son inscription puis de sa dernière connexion (`citizens.locale`) ; agent et consul = langue d’administration (`users.locale`) ; rendez-vous = langue de la réservation ; arabe de droite à gauche | CT |
| Titre de l'onglet du navigateur : suffixe « Plateforme consulaire » en français | NC (mineur) |
| Contenus saisis par les consulats (services, actualités) : traduits seulement si le consulat remplit les trois langues | par conception |
| Relecture native des traductions anglaises et arabes | AC |

## 9. Propositions de nouvelles fonctionnalités (à valider avant développement)

Ces propositions **élargissent le périmètre** : elles ne sont pas développées. Complexité : S (jours), M (1–2 semaines), L (plus).

> **Mise à jour du 13/11/2026** : les priorités 1 et 2 ont été développées, sauf le centre de messages citoyen ↔ consulat. Voir `RAPPORT-PRIORITES-1-2-2026-11-13.md`.

### Priorité 1 — combler les lacunes actuelles

| Fonctionnalité | Rôle | Problème résolu | Fonctionnement | Avantage utilisateur / consulat | Complexité | Dépendances |
|---|---|---|---|---|---|---|
| Emails dans la langue du destinataire | tous | Un citoyen arabophone reçoit des emails en français | Mémoriser la langue du compte citoyen ; tous les mails en `->locale()` avec gabarits traduits | Compréhension immédiate / moins d'appels | S | SMTP |
| Attribution des dossiers | consul, agent | Personne n'est responsable d'une pré-demande précise | Champ « agent assigné », filtre « mes dossiers », réattribution par le consul | Clarté du travail / suivi de la charge | M | — |
| Délais et retards | consul | Aucune alerte quand un dossier traîne | Délai cible par service ; badge « en retard » ; liste des dossiers hors délai sur le tableau de bord | Réponse plus rapide / qualité de service | M | Délai saisi par service |
| Historique d'un dossier | agent, consul, citoyen | On ne voit pas qui a fait quoi sur un dossier | Fil chronologique : dépôt, examens, décisions, étapes, retrait | Transparence / contrôle | S | Journal existant |
| Modèles de réponses | agent | Motifs de refus réécrits à chaque fois, avec des écarts | Bibliothèque de motifs par consulat, insérable dans l'examen | Gain de temps / homogénéité | S | — |

### Priorité 2 — fonctionnement quotidien

| Fonctionnalité | Rôle | Valeur | Complexité | Dépendances |
|---|---|---|---|---|
| Calendrier visuel des rendez-vous (jour / semaine) | agent, consul | Vision de la charge et des trous | M | — |
| Absences et disponibilités des agents | consul | Créneaux ajustés aux présences | M | Calendrier |
| Rapports PDF mensuels signables | consul, super admin | Rapport officiel transmissible | M | Bibliothèque PDF |
| Statistiques comparatives entre consulats | super admin | Repérer les consulats en difficulté | M | Agrégats existants |
| Centre de messages citoyen ↔ consulat | citoyen, agent | Remplace les allers-retours par email | L | Notifications |
| Recherche avancée des dossiers (référence, nom, période, statut) | agent, consul | Retrouver un dossier en secondes | S | — |
| Liste des pièces manquantes par dossier | agent, citoyen | Le citoyen sait exactement quoi apporter | S | Revue pièce par pièce (existe) |

### Priorité 3 — différenciation

| Fonctionnalité | Rôle | Valeur | Complexité | Dépendances / coûts |
|---|---|---|---|---|
| Notifications SMS / WhatsApp | citoyen | Atteint ceux qui lisent peu leurs emails | M | Fournisseur (coût par message), consentement |
| Paiement en ligne des frais (mobile money) | citoyen, agent | Moins d'espèces au guichet | L | Agrégateur de paiement, webhooks signés, conformité |
| Circuit de validation configurable (agent → consul) | consul | Double contrôle sur les actes sensibles | L | Attribution des dossiers |
| Téléchargement de documents délivrés (attestations) | citoyen | Moins de déplacements pour les actes simples | L | Signature électronique, cadre juridique |
| Immatriculation consulaire en ligne | citoyen, agent | Registre des ressortissants à jour | L | Validation juridique |
| Alertes d'urgence diffusées par le consulat | citoyen, super admin | Information de crise | M | SMS, modération |
| Tableau de bord décisionnel (tendances, prévisions de charge) | super admin, consul | Anticiper les pics | M | Historique suffisant |

### Classement par valeur pour les consulats

1. Attribution des dossiers + délais et retards (pilotage direct du service rendu).
2. Emails dans la langue du destinataire (qualité perçue, faible coût).
3. Historique d'un dossier et modèles de réponses (traçabilité et homogénéité).
4. Calendrier visuel et absences des agents (organisation quotidienne).
5. Notifications SMS / WhatsApp et paiement en ligne (fort impact, mais coûts et dépendances externes).
