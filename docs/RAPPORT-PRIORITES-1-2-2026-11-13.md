# Fonctionnalités de priorité 1 et 2 — 13 novembre 2026

Suite des propositions du rapport `RAPPORT-UTILISATEURS-LANGUES-PROPOSITIONS-2026-11-13.md` (section 9).
Rien n'est commité ni déployé.

## 1. Ce qui a été développé

### Priorité 1

| Fonctionnalité | Où | Règles |
|---|---|---|
| Attribution des dossiers | Pré-demandes : colonne « Attribué à », actions « Prendre en charge » et « Attribuer », filtres « Mes dossiers » et « Non attribués » | Seul un dossier « Soumise » est attribuable. Un agent ne peut se l'attribuer qu'à lui-même. Le consul (gestion de l'équipe) attribue ou libère. Le destinataire doit être actif, agent ou consul, et du même consulat. |
| Délais et retards | Services : « Délai cible de traitement (jours) ». Pré-demandes : colonne « Échéance » (rouge si dépassée) et filtre « En retard ». Tableau de bord : « Dossiers en retard » et « Mes dossiers », avec lien vers la liste filtrée | Échéance = date de soumission + délai cible. Sans délai saisi, pas de suivi. Seuls les dossiers « Soumise » peuvent être en retard. |
| Historique d'un dossier | Action « Historique » (personnel) ; section « Historique du dossier » dans l'espace citoyen | Événements : soumission (et nouvel envoi après complément), attribution, décision avec motif et pièces refusées. Le citoyen ne voit ni les attributions internes ni le nom des agents. |
| Modèles de réponses | Paramétrage → Modèles de réponses ; liste « Insérer un modèle de réponse » dans l'examen | Par consulat. Le personnel les consulte et les utilise ; seul un compte « gestion des contenus » les crée ou modifie. |

### Priorité 2

| Fonctionnalité | Où | Règles |
|---|---|---|
| Calendrier visuel | Accueil du public → Calendrier | Semaine du lundi au samedi : rendez-vous non annulés, jours de fermeture, absents. Navigation par semaine. Personnel avec gestion des rendez-vous. |
| Absences de l'équipe | Paramétrage → Absences de l'équipe | Le consul enregistre ; l'agent consulte. Un membre d'un autre consulat est refusé. Affichées dans le calendrier. |
| Rapport mensuel imprimable | Rapport mensuel → « Imprimer / PDF » | Page imprimable (enregistrer en PDF depuis le navigateur), dans la langue de l'utilisateur, RTL en arabe. Rendez-vous par service, encaissements, indicateurs des pré-demandes, ligne de visa du consul. Le personnel ne voit que son consulat. |
| Comparaison entre consulats | Tableau global (super admin) | Trois colonnes ajoutées : taux de présence, délai moyen de décision, dossiers en retard (mise en évidence si présence < 70 % ou retard > 0). |
| Recherche avancée | Pré-demandes | Recherche par nom ou email, filtres statut, service, période de soumission, mes dossiers, non attribués, en retard. Le numéro de dossier est affiché. |
| Pièces manquantes | Examen (personnel) et espace citoyen | Comparaison des pièces déposées (non refusées) avec la liste du service ; les pièces « selon la situation » sont listées à part. Suggestions de libellé dans le formulaire de dépôt. Une pièce nommée dans une autre langue compte si elle correspond à la même ligne d'une autre traduction (même nombre de lignes). |

Toutes les nouvelles chaînes sont traduites en anglais et en arabe (90 textes d'administration, 6 textes citoyens).

## 2. Non fait dans cette étape

- **Centre de messages citoyen ↔ consulat** (priorité 2, complexité L) : non développé.
- Les absences **n'ajustent pas automatiquement** la capacité des créneaux ; elles sont seulement affichées.
- Le « PDF » est une page imprimable ; aucune bibliothèque PDF n'a été ajoutée.
- Les notes d'historique sont enregistrées dans la langue de la personne qui agit (un motif rédigé en français reste en français dans l'interface anglaise ou arabe).

## 3. Tests

- Nouveau fichier `backend/tests/Feature/CaseManagementTest.php` (19 tests) : règles d'attribution, échéance et retard, widget, événements, pièces manquantes (y compris dans une autre langue), API citoyen sans nom d'agent, cloisonnement des modèles et absences, droits agent / consul, calendrier, liste des pré-demandes filtrée, rapport imprimable (son consulat uniquement, 403 sans droit), statistiques et tableau global.
- Suite complète du backend : **234 tests réussis (768 assertions)**, aucun échec.
- Vérification dans le navigateur avec des données de démonstration temporaires (puis `consulat:purge-demo --force`) : liste, prise en charge, examen avec pièces manquantes et modèle inséré, historique en arabe, calendrier en français et en anglais, rapport imprimable en arabe, espace citoyen en français et en anglais.

## 4. Base de données

Migration `2026_11_13_000027_case_management_features` : `pre_requests.assigned_to` / `assigned_at`, `services.target_days`, tables `pre_request_events`, `response_templates`, `staff_absences`. À exécuter en production avec `php artisan migrate --force`.

## 5. Messagerie citoyen ↔ consulat (ajoutée ensuite)

Le point laissé de côté ci-dessus est désormais développé.

- **Espace citoyen** : menu « Messagerie » ; liste des conversations avec aperçu du dernier message et compteur de non-lus ; nouveau message (sujet et texte, 2 000 caractères au maximum) ; réponse dans le fil. Depuis une pré-demande, lien « Une question sur ce dossier ? ».
- **Consulat** : menu « Messagerie citoyens » (badge des non-lus) ; fil de conversation ; réponse (avec modèles de réponses), clôture et réouverture ; action « Écrire au citoyen » depuis une pré-demande. Les réponses sont signées « Consulat » côté citoyen : le nom des agents n'est jamais transmis.
- **Règles** : un citoyen ne peut pas écrire dans une conversation clôturée ; il ne peut pas avoir plus de 5 conversations ouvertes ; une réponse du consulat rouvre la conversation ; le consulat ne voit que les conversations de son consulat.
- **Email** : chaque message du consulat déclenche un email dans la langue du citoyen, sans recopier le contenu (il se lit dans l'espace citoyen). Un échec d'envoi n'annule pas le message.
- **Tests** : `ConversationTest` (11 tests) et `ConversationListPreviewTest` (1 test).
- **Vérification** : envoi par le citoyen, réponse depuis l'administration, lecture côté citoyen (signée « Consulat »), liste en anglais.
- **Migration** : `2026_11_13_000028_create_conversations` (à exécuter en production avec `php artisan migrate --force`).

Limites : pas de pièces jointes ; pas de notification temps réel (l'email et le compteur suffisent) ; les messages ne sont pas traduits d'une langue à l'autre.

## 6. Association : élections en ligne, candidatures, fin d'adhésion

- **Fin d'adhésion au mandat** : quand le mandat d'un titulaire est terminé et qu'il n'a aucun autre mandat en cours, son adhésion passe à « inactif ». Une personne sans mandat garde son adhésion. Cela se fait après chaque élection, chaque archivage et chaque nuit (commande `consulat:end-expired-memberships`, 02:45). La réactivation reste manuelle.
- **Liaison au compte et email de nomination** : à la confirmation de l'email, le mandat est rattaché au compte et l'email de nomination part (une fois, seulement si le mandat est en cours).
- **Élection hors ligne** : inchangée dans son usage. Un bug est corrigé : un bureau saisi à la main n'était pas clos à l'élection suivante.
- **Vote en ligne** : fenêtre de candidature puis fenêtre de vote, configurées par l'admin (relation « Élections »). Électeurs et candidats : membres actifs de l'association, email confirmé. Un bulletin par électeur, au plus un candidat par fonction. Les bulletins (qui a voté) et les votes (pour qui) sont dans deux tables séparées : le consulat ne voit que les totaux.
- **Candidatures** : déposées par le citoyen pendant la candidature, validées ou refusées par le consulat (relation « Candidatures »). Un citoyen retire la sienne tant qu'elle est en attente.
- **Dépouillement et proclamation** : une fois le vote clos, l'admin proclame. Le candidat en tête de chaque fonction entre au bureau (même logique que l'élection hors ligne, avec les emails). Une égalité bloque la proclamation ; une fonction sans vote reste vacante et est signalée.
- **Espace citoyen** : page « Élections » (candidater, retirer, voter).
- **Tests** : `AssociationOnlineVotingTest` (14 tests) et `AssociationImprovementsTest` (6 tests). Suite complète : 266 tests réussis.
- **Vérification** : vote enregistré dans le navigateur avec des données de démo, puis suppression de ces données.

Limites : pas de tie-break en ligne (l'admin doit départager hors plateforme avant de proclamer) ; un citoyen ne peut être candidat qu'à une fonction par élection ; le bulletin ne protège pas contre un administrateur de base de données.

**Migrations à lancer** : `2026_11_13_000029_association_online_voting` (en plus de celles déjà notées).

## 7. Élections : règles automatiques (validées)

- **Type de vote** : par fonction, à la majorité absolue des voix exprimées. Sans majorité, second tour automatique entre les deux premiers (et les ex æquo à la deuxième place). Durée du second tour réglable : 24 h à 14 jours (72 h par défaut).
- **Départage au second tour** : membre adhérent depuis le plus longtemps, puis candidature la plus ancienne, puis numéro. Déterministe, sans intervention humaine.
- **Automatisme** : la commande `consulat:run-elections` (toutes les 5 minutes) fige la liste des électeurs à l'ouverture, dépouille à la fermeture, ouvre le second tour ou proclame. Aucun bouton à cliquer.
- **Totaux cachés** : aucun total pendant le vote, ni pour le consulat, ni pour les électeurs. Résultats publiés après traitement, avec la vérification du journal (route publique `/association/elections/{id}/results`).
- **Électorat figé** : à l'ouverture du vote. Un changement d'adhésion ensuite ne change pas qui peut voter.
- **Anonymat et reçu** : les bulletins (qui a voté) et les votes (pour qui) sont séparés. Chaque vote donne un code de reçu, affiché une seule fois ; seule son empreinte est conservée. L'électeur peut vérifier que son bulletin existe, sans révéler son choix.
- **Journal chaîné** : chaque vote est ajouté au journal avec l'empreinte de la ligne précédente. `VerifyVoteChain` recalcule tout ; une altération directe en base est détectée.
- **Verrous** : bulletins, votes, journal et résultats ne peuvent être ni modifiés ni supprimés depuis l'application. Les dates d'un scrutin ne peuvent plus changer une fois le vote ouvert.
- **Conditions par fonction** (à fixer par l'association, admin > Association > « Conditions par fonction ») : ancienneté minimale d'adhésion, réservé aux étudiants ou stagiaires, pièce justificative vérifiée par le consulat avant validation, nombre maximal de mandats déjà exercés. Sans condition enregistrée, aucune condition ne s'applique à la fonction.
- **Proclamation** : automatique, à partir des résultats. Les anciens mandats se terminent la veille et les emails partent.

**Limites à connaître** :
- « Mandats successifs » compte tous les mandats terminés dans la fonction, et non seulement les consécutifs.
- Les seuils (ancienneté, nombre de mandats) ne sont pas fixés : ils doivent venir des statuts de l'association.
- Une fonction sans aucune voix reste vacante, sans relance.
- Un administrateur ayant accès à la base de données pourrait relier un électeur à son vote.

**Migrations à lancer en production** : `2026_11_13_000029_association_online_voting` puis `2026_11_13_000030_association_election_rules`.

**Non traité dans ce lot** (périmètre demandé, à cadrer avant développement) : registre financier de l'association, double authentification obligatoire pour les comptes qui gèrent élections et bureau, historique des modifications du bureau, plafonds sur les actions sensibles, export public du registre anonymisé, alertes sur les comptes multi-postes.

## 8. Démarches : liaison pré-demande ↔ rendez-vous

- **Rattachement automatique** : à la réservation, le dernier dossier **validé** du citoyen pour le même service est rattaché au rendez-vous. Sans dossier validé, la réservation reste possible, sans rattachement.
- **Rattachement explicite** : seul un dossier du même citoyen, du même consulat et du même service peut être choisi, et il doit être **validé**. Sinon refus.
- **Au guichet** (file du jour) : à l'arrivée, l'agent voit le statut du dossier. Si validé : la liste des pièces originales à présenter. Si non validé : avertissement de ne pas traiter la démarche. Si aucun dossier : avertissement avec la liste des pièces à vérifier sur place.
- **Liste des rendez-vous (administration)** : colonne « Dossier en ligne » (statut coloré).
- **Espace citoyen** : le statut du dossier apparaît dans le détail du rendez-vous (API : champ `pre_request`, statut seulement, jamais les pièces).
- **Tests** : `AppointmentPreRequestLinkTest` (7 tests). Suite complète : 297 tests réussis.
- **Vérification** : l'API renvoie le dossier lié. Le rendu du panneau de détail n'a pas été contrôlé à l'écran.

**Migration à lancer en production** : `2026_11_14_000031_link_appointments_to_pre_requests`.

**Décision à confirmer** : un rendez-vous peut être pris sans dossier validé (il est signalé au guichet). Une alternative plus stricte serait de l'interdire pour les services qui exigent des pièces.

## 9. Gouvernance, registre financier, démarches (14 novembre 2026)

**Association — règles anti-fraude**
- **Conflit d'intérêts** : un compte du personnel (ou ayant un compte d'administration, même email) ne peut pas être candidat, ni membre de la commission.
- **Double contrôle** : président et trésorier : pièce vérifiée par deux personnes différentes, qui ne sont pas le candidat.
- **Mandats consécutifs** : la limite compte les mandats qui se suivent (interruption d'un an = remise à zéro), et non le total.
- **Plafond** : un seul archivage de bureau hors élection par 30 jours (sauf super admin).
- **Commission électorale** : 3 membres maximum, hors personnel. Attestation du résultat publié (2 attestations = résultat attesté). Elle n'a pas le pouvoir de modifier le résultat.
- **Poste vacant** : élection partielle automatique, limitée aux postes vacants, candidatures 7 jours puis vote 7 jours. Elle ne remplace que les fonctions concernées.
- **Double authentification obligatoire** : super admin, consul, et tout compte qui gère élections, bureau ou utilisateurs (paramétrable : `TWO_FACTOR_FOR_MANAGERS`).
- **Registre financier** : cotisations saisies par le trésorier ; dépenses proposées par un membre du bureau et approuvées par un autre ; aucun solde négatif ; écritures approuvées figées. Publication : solde et dernières écritures, sans aucun nom.

**Démarches**
- **Règle de rendez-vous** par service : « rendez-vous seulement avec un dossier validé » (désactivée par défaut, à activer par service).
- **Frais visibles** dans la pré-demande (tarif en vigueur à la date du jour, réglé au guichet).
- **Priorité urgente** sur une pré-demande : elle remonte en tête de liste.
- **Statuts de suivi** : « En production » et « Expédié » ajoutés au suivi au guichet.

**Finitions**
- ESLint répare (dépendance réinstallée) ; une règle de hooks est passée en avertissement, documentée comme dette.
- Page 404 : statut HTTP 404 vérifié.

**Migrations à lancer en production** (dans l'ordre) : `2026_11_14_000032_association_governance`, `2026_11_14_000033_partial_elections_unique_year`, `2026_11_14_000034_association_ledger`, `2026_11_14_000035_demarches_rules`.

**Tests** : suite complète, 316 tests réussis.

**Reste à faire** (détail dans le document de cadrage) : interface du registre financier ; export CSV public ; titres des pages consulaires encore en français en anglais et arabe (une vingtaine de pages) ; chronologie unique du citoyen ; dates d'expiration des documents ; règles spécifiques de visa, carte consulaire et cas particuliers (perte de passeport, mineur, procuration) à fournir par le consulat ; canal SMS ou WhatsApp (prestataire à choisir) ; paiement en ligne (prestataire à choisir).

## 10. Registre financier, export, chronologie, validité, titres (14 novembre 2026, suite)

- **Registre financier (interface)** : page « Finances de l’association » (espace citoyen, membres du bureau). Solde, écritures, cotisation (trésorier), dépense (proposition), approbation par un autre membre. Une écriture approuvée est figée.
- **Affichage public** : solde et dernières écritures sur la page association, sans aucun nom.
- **Export CSV public** : `/v1/{consulat}/association/export.csv` (registre approuvé, activités publiées, résultats publiés). Aucune donnée d'adhérent.
- **Chronologie unique** : page « Ma chronologie » (espace citoyen). Fusionne dépôt et examen du dossier, rendez-vous, arrivée au guichet, suivi du document, échéances. Les événements internes (attribution à un agent) ne sont jamais montrés.
- **Date d'expiration des pièces** : facultative au dépôt ; alerte « expire bientôt » (60 jours) ou « expirée ».
- **Validité d'un document délivré** (carte consulaire, visa, passeport) : le personnel renseigne la date « valable jusqu’au » sur le dossier.
- **Rappels** : `consulat:send-validity-reminders` (08h10) envoie un email, dans la langue du citoyen, 30 jours avant chaque échéance (une seule fois par élément).
- **Cas particuliers** : le personnel signale une situation (document perdu ou volé, mineur sans parent présent, procuration, voyage urgent, autre). La plateforme la signale et la suit ; les pièces exigées pour chaque cas sont à définir par le consulat.
- **Titres de pages** : 24 pages consulaires affichent leur titre dans la langue de l'URL (FR, EN, AR).

**Ce qui n'est PAS inventé** : les règles de visa (durée, validité du passeport, finalité), les pièces de carte consulaire, et les pièces de chaque cas particulier. Le consulat doit les fournir ; la plateforme les appliquera alors sans changement de structure.

**Migrations à lancer en production** : `2026_11_14_000036_documents_validity_and_cases`.

**Tests** : suite complète, 322 réussis.

## 11. Double authentification : appareils de confiance (15 novembre 2026)

- Après un code à 6 chiffres réussi, le navigateur est mémorisé **jusqu'au prochain renouvellement annuel** (`TWO_FACTOR_RENEWAL_DAY`, par défaut 1er janvier).
- Le **mot de passe reste demandé à chaque connexion**. Sur un nouvel appareil ou navigateur, le code à 6 chiffres est redemandé.
- Le changement de mot de passe oublie tous les appareils. Le bouton « Oublier tous les appareils » des réglages le fait à la demande.
- Les appareils expirés sont purgés chaque nuit (04h00).
- Récupération : `consulat:reset-two-factor <email>` (accès serveur uniquement).

**Migration à lancer** : `2026_11_15_000037_two_factor_trusted_devices`.
**Tests** : 329 réussis.
