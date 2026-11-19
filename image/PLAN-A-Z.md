# Couverture des captures, de A à Z

Légende : **Fait** = capturé · **Partiel** = capturé en partie · **À faire** = pas encore capturé.
Données : base locale de démonstration. Ne pas publier les pages « Sécurité du compte » ni les reçus de vote.

## 1. Super administrateur
| # | Cas d'usage | Statut |
|---|---|---|
| 1.1 | Connexion + double authentification | Partiel (connexion capturée, 01-super-admin/58 à 59 ; écran de code 2FA non capturé : appareil déjà approuvé) |
| 1.2 | Tableau de bord | Fait |
| 1.3 | Liste des consulats | Fait |
| 1.4 | Créer un consulat (assistant en 5 étapes : identité, services, créneaux, abonnement, consul) | Partiel (étapes 1 à 5 captées, 01-super-admin/78 à 84 ; création non validée, aucun consulat créé) |
| 1.5 | Modifier un consulat, enregistrer un paiement d'abonnement | Fait (formulaire 01-super-admin/75 ; paiement de test enregistré, 76 et 85 ; période à vérifier, voir ci-dessous) |
| 1.6 | Gérer les utilisateurs : liste, création, modification, lien d'activation | Fait (01-super-admin/55 à 57, 60 à 61 ; envoi du lien annulé, rien envoyé) |
| 1.7 | Services : liste, création, frais, délai, validité passeport | Fait (01-super-admin/62 à 63, 73 à 74) |
| 1.8 | Tableau global (statistiques par consulat) | Fait |
| 1.9 | Journal d'activité, filtres | Fait (01-super-admin/53 à 54) |
| 1.10 | Pré-demandes, citoyens, signalements, encaissements, abonnements (listes) | Fait (listes vides) |
| 1.11 | Pages de contenu : actualités, FAQ, pages | Fait (01-super-admin/64 à 67) |
| 1.12 | Rapport mensuel, calendrier, plan de charge | Fait (01-super-admin/68 à 70 ; plan de charge vide pour le super administrateur, sans consulat choisi) |

## 2. Consul (administrateur du consulat)
| # | Cas d'usage | Statut |
|---|---|---|
| 2.1 | Connexion + activation obligatoire de la double authentification | Fait |
| 2.2 | Tableau de bord | Fait |
| 2.3 | Valider la résidence d'un citoyen (compte en attente → actif) | Fait |
| 2.4 | Rendez-vous, services, équipe, actualités | Fait |
| 2.5 | Calendrier, plan de charge, abonnement, modèles de réponses, absences, créneaux | Fait |
| 2.6 | Associations : fiche, reconnaissance officielle | Fait |
| 2.7 | Élections : formulaire de création (dates, mode en ligne/hors ligne) | Fait |
| 2.8 | Élections : refus d'une seconde élection la même année (message visible) | Fait |
| 2.9 | Candidatures : valider ou refuser | Fait |
| 2.10 | Résultats d'une élection | Fait |
| 2.11 | Pré-demande : examen pièce par pièce, décision, demande de pièces | Fait |
| 2.12 | Encaissement d'un reçu consulaire | Fait (02-consul-agent/23 à 25) |

## 3. Agent et secrétariat
| # | Cas d'usage | Statut |
|---|---|---|
| 3.1 | Connexion (sans double authentification) | Fait |
| 3.2 | Ma file de travail | Fait |
| 3.3 | Pré-demandes soumises, examen | Fait |
| 3.4 | File du jour, messagerie, rapport mensuel | Fait |
| 3.5 | Rendez-vous : enregistrer l'arrivée, ticket | Partiel (arrivée et ticket A-001 captés, 02-consul-agent/34 à 35 ; limite : jour du rendez-vous) |
| 3.6 | Secrétariat : ce qu'il voit et ce qui lui est refusé | Fait (secrétariat, 29 à 33) |

## 4. Citoyen
| # | Cas d'usage | Statut |
|---|---|---|
| 4.1 | Inscription (formulaire, déclarations, demande reçue) | Fait |
| 4.2 | Confirmation par e-mail | Fait |
| 4.3 | Connexion et tableau de bord | Fait |
| 4.4 | Démarche en ligne : brouillon, pièces manquantes, envoi bloqué | Fait |
| 4.5 | Démarche : téléversement de pièces, envoi complet | Fait (03-citoyen/58 à 60, 71 à 72) |
| 4.6 | Prendre un rendez-vous (choix du service, date, créneau, confirmation) | Fait (03-citoyen/16 à 23) |
| 4.7 | Mes rendez-vous : annuler, reprogrammer, ICS | Fait (annulation et reprogrammation 03-citoyen/61 à 66 ; export ICS 03-citoyen/77, fichier valide vérifié sans téléchargement) |
| 4.8 | Messagerie : écrire au consulat, lire la réponse | Fait (03-citoyen/29 à 30 ; réponse du consulat, 02-consul-agent/20 à 22) |
| 4.9 | Mon profil, ma famille, ma chronologie, mes signalements | Fait |
| 4.10 | Mot de passe oublié, réinitialisation | Fait (03-citoyen/24 à 27) |
| 4.11 | Élections : liste, conditions par fonction, candidature | Fait |
| 4.12 | Élections : vote et reçu | Fait (vote, reçus et second tour : 26, 30 à 31, 47 à 48) |
| 4.13 | Élections : résultats publiés | Fait |

## 5. Président et bureau d'association
| # | Cas d'usage | Statut |
|---|---|---|
| 5.1 | Espace du bureau : tableau de bord | Fait |
| 5.2 | Publier une activité | Fait |
| 5.3 | Ajouter un adhérent, désactiver | Fait |
| 5.4 | Registre des adhérents | Fait |
| 5.5 | Finances : cotisation, dépense proposée, approbation | Fait (43 à 46, 51) |
| 5.6 | Journal des actions du bureau | Fait (28) |
| 5.7 | Export CSV du registre | Fait (28) |
| 5.8 | Modifier les informations publiques | Fait (04-association/69 à 70) |
| 5.9 | Élu : espace de la fonction obtenue, et responsable des activités | Fait (04-association/67 à 68) |

## 6. Élections, de bout en bout (gouvernance : le consulat ouvre et organise)
| # | Étape | Statut |
|---|---|---|
| 6.1 | Le consulat crée l'élection | Fait |
| 6.2 | Candidature d'un membre actif (déclaration, choix de fonction) | Fait |
| 6.3 | Le consulat valide la candidature | Fait |
| 6.4 | Liste des candidats affichée au citoyen | Fait |
| 6.5 | Vote anonyme | Fait |
| 6.6 | Proclamation, résultats | Fait |
| 6.7 | Élection partielle pour les postes vacants (correction faite) | Fait |
| 6.8 | Second tour | Fait (47 à 50) |
| 6.9 | Vérification du reçu | Fait (30 à 31, 48) |

## 7. Parcours transverses
- Anomalies corrigées : création d'élection (répéteur hors ligne), message trompeur de phase, refus silencieux, élection partielle absente, libellé d'élection partielle.
- Gouvernance vérifiée par tests : le consulat seul peut ouvrir une élection ; l'agent et le président ne le peuvent pas.

## Mise à jour (tour suivant)
- Corrigé : indicateur « Tous à jour » alors qu'aucun abonnement n'existe (affiche désormais le nombre de consulats sans abonnement). Capture 01-super-admin/14.
- Corrigé : tarifs de la carte consulaire (2 000 papier / 5 000 numérique), listes et données de démonstration. Capture 01-super-admin/13.
- Ajouté : journal des actions du bureau et export CSV du registre, avec boutons et libellés. Capture 04-association/28.
- Corrigé : « Espace du bureau » n'est plus proposé aux citoyens sans fonction.
- Corrigé : texte explicatif près du bouton « Envoyer au consulat » désactivé.
- Corrigé : formulaire d'activité vidé après publication (évite la double publication).
- Corrigé : élection partielle identifiée comme telle côté citoyen.
- À faire : captures du citoyen sans fonction, de la prise de rendez-vous, du mot de passe oublié, de l'encaissement, du second tour.

## Mise à jour (captures suivantes)
- Super administrateur : formulaires de création (service, utilisateur, actualité, FAQ, page) : captures 01-super-admin/15 à 19. Création réelle non faite (données de test déjà présentes).
- Citoyen : prise de rendez-vous complète (service, date, créneau, coordonnées, récapitulatif, confirmation) : captures 03-citoyen/16 à 23. Un rendez-vous de test existe pour le 24 novembre 2026.
- Citoyen : mot de passe oublié, lien, nouveau mot de passe, confirmation : captures 03-citoyen/24 à 27.
- Corrigé : la case de consentement du rendez-vous n'affiche plus une bulle native du navigateur, le message du site est ramené à l'écran.
- Limite : l'arrivée au guichet (ticket) ne peut être capturée que le jour même du rendez-vous.
- Limite : encaissement d'un reçu consulaire : à capturer dans une prochaine étape.

## Mise à jour (messagerie et encaissement)
- Citoyen : mes rendez-vous avec rendez-vous confirmé (03-citoyen/28), messagerie : nouveau message et envoi (03-citoyen/29 à 30).
- Consul : messagerie, lecture et réponse (02-consul-agent/20 à 22), encaissement : formulaire, reçu enregistré, mode obligatoire (02-consul-agent/23 à 25).
- Corrigé : un encaissement sans service était accepté (« Service — » dans le journal). Le service est maintenant obligatoire, avec test.
- Limite : le formulaire d'encaissement affiche les options de liste natives, non visibles sur les captures.

## Mise à jour (élections complètes)
- Citoyen : vérification du reçu, cas refusé et cas reconnu (04-association/30 à 31). Libellé « Élection partielle » vérifié (29).
- Consul : onglets commission électorale (vide et ajout d'un membre), conditions par fonction, activités, archives (04-association/32 à 36).
- Consul : élection hors ligne avec bureau élu, formulaire et résultat (04-association/37 à 38).
- Règles vérifiées par tests : déclaration d'absence de lien familial, candidature refusée hors postes vacants d'une élection partielle, élection partielle.

## Restes à faire (obsolète, voir « État à jour » en fin de fichier)
- Deuxième tour : logique testée, pas encore de parcours à l'écran.
- Commission électorale : ajouter trois membres et l'attestation (interface).
- Secrétariat : créer un compte secrétariat et capturer son espace.
- Super administrateur : journal filtré, modifier un consulat, enregistrer un paiement d'abonnement.
- Guichet : arrivée et ticket (le jour même du rendez-vous).
- Citoyen sans fonction : menu sans « Espace du bureau ».
- Membres du bureau autres que président (secrétaire, trésorier, responsable des activités) : espaces à capturer.

## Mise à jour (secrétariat)
- Consul : création d'un compte secrétariat par le formulaire (02-consul-agent/26, 27).
- Secrétariat : tableau de bord (29), rendez-vous (30), messagerie (31), encaissements refusés 403 (32), pré-demandes en lecture (33). Avant correction : 28.
- Corrigé : le secrétariat voyait un compteur de dossiers à décider qu'il ne peut pas traiter, et le libellé de son rôle était « Agent consulaire ». Test ajouté, libellé ajouté en anglais et arabe.
- Restes : capture de l'encaissement refusé dans l'interface (fait via 403), second tour, commission électorale (3 membres), paiement d'abonnement, journal filtré, consulat modifié, autres fonctions du bureau.

## Mise à jour (super administrateur)
- Fiche d'un consulat (modifier) : 01-super-admin/20.
- Enregistrement d'un paiement d'abonnement (formulaire) : 01-super-admin/21. Non enregistré.

## Restes réels (obsolète, voir « État à jour » en fin de fichier)
- Deuxième tour : parcours à l'écran (plusieurs tours, nécessite trois votants et une élection sans majorité).
- Commission électorale : trois membres non-personnel et attestation (nécessite trois comptes citoyens validés).
- Autres fonctions du bureau (secrétaire, trésorier, responsable des activités) : comptes et espaces.
- Citoyen sans fonction : menu sans « Espace du bureau ».
- Guichet : arrivée et ticket le jour même du rendez-vous.

## Mise à jour (commission et comptes de test)
- Comptes de test créés (citoyens actifs, domaine example.test) : electeur1 à electeur5, secretaire, sansfonction. Mot de passe de test identique aux autres comptes de démo (non écrit ici).
- Commission électorale : trois membres ajoutés (39). Capture du formulaire d'ajout (40).
- ANOMALIE OUVERTE : dans la fiche association, le champ « Citoyen » du formulaire « Ajouter un membre » (commission) ferme la fenêtre dès qu'on ouvre la liste. Les membres ont donc été ajoutés via l'action métier, pas via l'interface. À corriger (le même type de formulaire est utilisé pour le bureau).
- Restes : deuxième tour (élection sans majorité, trois candidats, votes, second tour, proclamation), secrétaire du bureau (espace et restrictions), citoyen sans fonction (menu), guichet (arrivée, ticket, file du jour).

## Anomalie corrigée : formulaire « Ajouter un membre » (commission)
- Cause probable : la fenêtre se fermait au clic à l'extérieur, la liste de recherche s'affichant hors de la fenêtre.
- Correction : `closeModalByClickingAway(false)` sur l'action d'ajout. Après correction, la fenêtre reste ouverte au clic sur la liste (vérifié à l'écran).
- Test ajouté : `CommissionAddMemberTest` (ajout par le formulaire). 385 tests verts.
- Limite : la liste déroulante n'est pas visible dans la fenêtre de test du navigateur (zone coupée), la sélection est donc vérifiée par le test.

## Mise à jour (citoyen sans fonction, guichet)
- Citoyen sans fonction (electeur/sansfonction) : menu sans « Espace du bureau » (03-citoyen/31), accès direct refusé (32), page élections avec candidature à l'élection partielle (33). Anomalie corrigée : le lien bureau était visible à tous.
- Guichet : rendez-vous du jour créé pour un citoyen, file du jour (02-consul-agent/34), arrivée et ticket A-001 attribué (35).
- Restes : deuxième tour (élection sans majorité, votes, second tour), secrétaire du bureau (espace et limites), trésorier (encaissement de cotisation), vérification de la commission (attestation).

## Mise à jour (trésorier et finances)
- Trésorier (electeur5) : cotisation de 5 000 FCFA enregistrée (43). Solde affiché 5 000 FCFA.
- Anomalie corrigée : le trésorier ne voyait que le formulaire « Enregistrer une cotisation ». Il ne pouvait donc pas proposer de dépense depuis l'interface, alors que l'API l'y autorise. Ajout d'un sélecteur à deux onglets (`AssociationLedger.tsx`). Capture du sélecteur (45).
- Dépense de 1 500 FCFA proposée par le trésorier (44), puis approuvée par le secrétaire élu, citoyen.demo3 (46). Solde après approbation : 3 500 FCFA.
- Point à trancher : une cotisation saisie par le trésorier apparaît « approuvée par » le trésorier lui-même. Le libellé est exact, mais la règle « approbation par un autre membre » ne s'applique qu'aux dépenses.
- Point à corriger (mineur) : le bouton du formulaire de dépense reste « Enregistrer » ; « Proposer » serait plus clair.
- Restes : deuxième tour (élection sans majorité, votes, second tour), attestation de la commission.

## Mise à jour (second tour, 2028)
- Élection 2028 (sans majorité) : trois candidats à la présidence (electeur1, electeur2, electeur3), pièce vérifiée par deux personnes différentes (consul, secrétariat).
- Premier tour : 2 voix, 2 voix, 1 voix sur 5 exprimés, donc pas de majorité absolue. Second tour entre les deux premiers (2 / 5).
- Second tour : electeur1 A, electeur2 A, electeur3 B (scripts), electeur4 et electeur5 votent dans l'interface pour B. Résultat : Électeur Test Deux élu (3 / 5).
- Captures : premier tour et candidats qualifiés (47), reçu de vote (48), résultat proclamé côté citoyen (49), attestation de la commission 2 / 2 (50).
- Anomalie de données (test) : la commission était composée de trois candidats, donc aucun ne pouvait attester (blocage correct, par règle). Commission remplacée par electeur4, electeur5 et sansfonction (non candidats), puis attestations 2 / 2.
- Effets de la proclamation : le bureau a été remplacé. Démo Trois (secrétaire, mandat 2029) et electeur5 (trésorier) ne sont plus en fonction. Les autres postes vacants ont ouvert une élection partielle 2028 (candidatures ouvertes, vote 26 nov. → 3 déc. 2026).
- Non fait : captures de l'interface consul / admin des résultats (modal « Résultats »).

## Mise à jour (rétablissement du bureau de test et anomalies)
- Mandats rétablis : secrétaire (citoyen.demo3, fin 14 juin 2029) et trésorier (electeur5, fin 19 nov. 2028). Adhésions réactivées (la fin de mandat les avait désactivées).
- Bureau en exercice : président élu Électeur Test Deux (second tour 2028). Les autres postes vacants sont couverts par l'élection partielle 2028.
- Anomalie corrigée : le bouton du formulaire de dépense affiche « Proposer » (et non « Enregistrer »). Capture 51.
- Anomalie corrigée : une cotisation saisie par le trésorier n'affiche plus « approuvée par » (c'était la même personne).
- Commission : remplacée par electeur4, electeur5, sansfonction (non candidats) pour permettre l'attestation.

## État à jour (remplace « Restes réels » ci-dessus)
Tableaux corrigés : deuxième tour, reçu de vote, commission, finances, journal, export, encaissement et secrétariat sont faits.

Reste à capturer ou à tester :
- Super administrateur : connexion et double authentification séparées (1.1) ; création d'un consulat, étapes 2 à 5 (1.4) ; enregistrement effectif d'un paiement d'abonnement (1.5) ; utilisateurs : liste, création, modification, lien d'activation (1.6) ; services : création, frais, délai, validité passeport (1.7) ; journal filtré (1.9) ; rapport mensuel, calendrier et plan de charge (1.12).
- Citoyen : téléversement de pièces jusqu'à l'envoi complet d'une démarche (4.5) ; mes rendez-vous : annuler, reprogrammer, ICS (4.7).
- Bureau : espace du responsable des activités (5.9 ne couvre que l'élu) ; modification des informations publiques (5.8).
- Élections : résultats dans l'interface consul / admin, modal « Résultats » (non capturé).
- Limites : ticket de guichet capturable seulement le jour du rendez-vous ; liste déroulante d'ajout à la commission coupée à l'écran (vérifiée par test).

## Mise à jour (suite des captures)
- Consul : résultats du second tour dans le modal « Résultats » de la fiche association : 04-association/52.
- Super administrateur : journal d'activité (liste et filtre « Rôle attribué ») : 01-super-admin/53 et 54.
- Super administrateur : utilisateurs, liste (55), formulaire de création, sans enregistrer (56), liste des rôles (57).
- Connexion en test : les comptes admin.tchad et superadmin ont la double authentification. Les codes ont été générés localement pour ces comptes de test.
- Anomalie observée (non corrigée) : dans la fiche association, l'élection partielle 2028 et l'élection principale 2028 ont la même année et ne se distinguent que par la phase. Rien n'indique « partielle » dans la colonne Année.
- Restes après cette série : 4.5 (téléversement de pièces), 4.7 (annuler et reprogrammer un rendez-vous), 5.9 (espace du responsable des activités), 1.6 (modification et lien d'activation), 1.7 (services), 1.4 (étapes 2 à 5), 1.5 (paiement enregistré), 1.12.


## Mise à jour (4.5, 4.7, 5.9)
- 4.5 : démarche « Carte consulaire (papier) » : informations, puis téléversement de pièces (03-citoyen/58 à 60).
- Anomalie corrigée : une pièce pouvait être téléversée sans nom (le nom de fichier servait de libellé). Le champ fichier est maintenant désactivé tant que le nom n'est pas saisi, avec un message. Capture 59 (avant) et 60 (après).
- 4.7 : rendez-vous passeport du 24 novembre (TCH-SN-20261124-BRJ4SL) pris, déplacé au 26 novembre 10:00, puis annulé (03-citoyen/61 à 66).
- Anomalie corrigée : « Voir / gérer » depuis la liste menait à « Requête refusée ». Le proxy n'acceptait que des segments en minuscules, alors que les références sont en majuscules. Correction de l'expression de validation (route BFF). Capture 61 (avant) et 62 (après).
- 5.9 : fonction « Responsable des activités (sport, culture) » attribuée à sansfonction (mandat jusqu'au 19 nov. 2028, compte de test). Espace : publication d'activité, registre, journal, sans ajout d'adhérent ni informations publiques (04-association/67, 68).
- Vérification : la publication d'une activité n'a créé qu'une entrée au journal. Les deux entrées identiques du 19/11 à 12:21 datent d'avant la correction de la double publication.


## Mise à jour (captures manquantes)
- 1.1 : connexion (58) et tableau de bord après connexion (59). L'écran de code 2FA n'apparaît pas sur cet appareil, déjà approuvé ; il faut un navigateur neuf pour le capturer.
- 1.6 : modification d'un utilisateur (60), confirmation de l'envoi du lien d'activation annulée (61). Rien n'a été envoyé.
- 1.7 : liste (62) et formulaire de création (63), sans enregistrer.
- 1.11 : actualités (64), modification d'une actualité (65), FAQ (66), pages légales (67).
- 1.12 : rapport mensuel (68), calendrier (69), plan de charge (70). Le plan de charge est vide pour le super administrateur, qui n'a pas de consulat choisi : à voir depuis le compte consul.
- 4.5 : pièce photo ajoutée, envoi complet (71), avant envoi (72). La démarche est « Soumise ».
- 5.8 : formulaire rempli (04-association/69), informations enregistrées (70). Valeurs de test, domaine example.test.
- Non fait : 1.4 (création de consulat, étapes 2 à 5), 1.5 (enregistrement réel d'un paiement d'abonnement), 1.7 (fiche de modification d'un service), 4.7 (export ICS : aucun rendez-vous actif, le seul a été annulé), 3.5 (guichet, seulement le jour même).


## Mise à jour (reste à faire et capturé)
- 1.4 : assistant en 5 étapes capté (01-super-admin/78 à 84). Étape 4 : le mode de paiement est obligatoire (message capté, 83). Création non validée : aucun consulat n'a été créé.
- 1.5 : paiement de test enregistré sur tchad-senegal (virement, 50 000 FCFA). Le formulaire a été rempli avec une période du 01/11/2026 au 31/10/2027, mais la liste affiche 19/11/2026 → 18/11/2027. Cause probable : la saisie des dates via l'outil n'a pas été transmise à l'application ; à vérifier à la main avec une saisie réelle.
- 1.7 : fiche de modification d'un service (73, 74), sans enregistrer.
- 4.7 : rendez-vous de test TCH-SN-20261127-QZTRF7 (27 nov., 09:00). Export ICS vérifié : 200, text/calendar, DTSTART 20261127T090000. Le bouton déclenche un téléchargement, non lancé.
- Effets de test à connaître : la démarche de l'électeur5 est soumise ; les informations publiques de l'association de démonstration ont été modifiées (contact.asso@example.test) ; tchad-senegal a un paiement de test.
