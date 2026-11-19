# Cadrage : décisions à prendre, ce qui reste (association et démarches consulaires)

Date : 14 novembre 2026. Document de travail : il décrit la logique de chaque règle, les conséquences d'un choix,
la proposition, et ce qu'il faut obtenir de l'association ou du consulat avant développement.
Rien de ce document n'est encore développé, sauf mention contraire.

---

## Partie 1 — Points à trancher pour les élections

### 1.1 Seuil d'ancienneté d'adhésion
- **Logique** : il faut être assez ancien pour connaître l'association, mais assez tôt dans le parcours pour que la règle ne ferme pas la porte aux étudiants. Un étudiant reste en moyenne 2 à 4 ans.
- **Conséquence** : 24 mois exclurait la plupart des candidats ; 1 mois permettrait à quelqu'un d'arriver et de se présenter aussitôt.
- **Proposition** : 6 mois pour un poste ordinaire ; 12 mois pour président et trésorier.
- **À obtenir** : le chiffre retenu dans les statuts.

### 1.2 Limite de mandats : consécutifs ou total
- **Logique** : la limite sert à éviter qu'une personne reste au pouvoir indéfiniment. Ce qui compte, c'est la continuité, pas le total d'une vie.
- **Conséquence** : compter tous les mandats d'une personne pénalise quelqu'un qui a exercé une fois il y a 5 ans puis revient.
- **Proposition** : compter les mandats **consécutifs** dans la même fonction ; une interruption d'un an complet remet le compteur à zéro. Maximum 2 mandats consécutifs.
- **Écart avec le code actuel** : le code compte aujourd'hui tous les mandats terminés. À corriger si la règle est validée.

### 1.3 Poste vacant (aucun vote, ou démission en cours de mandat)
- **Logique** : un poste vacant ne doit jamais être pourvu par le consulat seul, sinon le consulat choisit le bureau.
- **Proposition** : élection partielle pour ce seul poste, ouverte dans les 30 jours. Si le poste reste vacant, il est pourvu par un vote des membres du bureau élu (et non du consulat).
- **Écart avec le code actuel** : aujourd'hui un poste sans vote reste vacant, sans relance ni règle de remplacement.
- **À obtenir** : le délai et la règle de remplacement dans les statuts.

### 1.4 Garanties contre la fraude : une limite technique à connaître
- **Logique** : un logiciel peut prouver qu'un résultat n'a pas été modifié après coup (journal chaîné, reçus, résultats figés). Il ne peut pas empêcher un administrateur ayant accès à la base de données de connaître le lien entre un électeur et son vote, ni de modifier la base avant publication.
- **Proposition** :
  1. publier l'empreinte finale du journal à la fermeture, en un lieu que le consulat ne contrôle pas (affichage au siège de l'association, ou courriel à une commission) ;
  2. désigner une commission électorale de 3 membres, hors personnel du consulat, qui peut recalculer et signer le résultat ;
  3. journaliser tout accès à la base pendant un scrutin.
- **Ce que le logiciel ne peut pas garantir seul** : la confiance absolue. Il faut une tierce personne.
- **À obtenir** : l'accord de l'association sur la composition de la commission.

### 1.5 Conflit d'intérêts entre le consulat et les candidats
- **Logique** : un membre du personnel du consulat qui se présente, ou qui valide les candidatures d'un proche, fausse le processus.
- **Proposition** : interdire la candidature aux comptes du personnel (agent ou consul) ; le personnel qui valide une pièce ne peut pas être candidat à la même élection ; déclaration de lien familial obligatoire pour les candidats.
- **Écart avec le code actuel** : le code n'interdit pas aujourd'hui qu'un membre du personnel soit candidat s'il a aussi un compte citoyen et une adhésion.
- **À obtenir** : la liste des personnes qui relèvent de cette interdiction.

### 1.6 Qui valide la pièce justificative
- **Logique** : la vérification d'une pièce (ex. diplôme de trésorier) doit être faite par quelqu'un qui n'a pas d'intérêt dans le choix.
- **Proposition** : un membre du personnel du consulat vérifie, et une seconde personne contresigne pour les postes président et trésorier.
- **À obtenir** : la liste des pièces exigées par poste.

---

## Partie 2 — Ce qui reste pour l'association

| Chantier | Logique | Décision nécessaire avant développement |
|---|---|---|
| **Registre financier** (cotisations, dépenses) | L'argent des membres doit être géré par le bureau élu ; le consulat ne peut que consulter. | Montant de la cotisation ; mode de paiement (guichet, virement, mobile) ; qui encaisse ; qui valide une dépense ; si le consulat peut voir les totaux. |
| **Double authentification obligatoire** pour les comptes qui gèrent élections et bureau | Ces comptes peuvent changer qui est élu ; un mot de passe seul est insuffisant. | Quels rôles sont concernés ; le code existe déjà (page « Sécurité du compte »), il faut seulement l'imposer. |
| **Historique des modifications du bureau** | La transparence protège le bureau comme le consulat. | Qui voit l'historique : le bureau seul, les membres, le super admin. Le journal enregistre déjà les champs modifiés, mais sans les valeurs. |
| **Plafonds sur les actions sensibles** | Empêcher qu'un compte vide le bureau d'un coup. | Seuils : ex. un seul archivage de bureau par mois hors élection. |
| **Export public anonymisé** | Publier les résultats oui, la liste des membres non (vie privée). | Ce qui est public : bureau en exercice, activités, résultats, ni noms d'adhérents ni contacts. |
| **Conflits multi-postes** | Mieux vaut bloquer un conflit que l'signaler après coup. | Voir 1.5 : la règle est une interdiction, pas une alerte. |

**Ordre recommandé** : 2FA (rapide, risque élevé) → conflits d'intérêts (1.5) → limite de mandats (1.2) → historique → export public → registre financier (le plus long, car il dépend de la façon dont l'argent circule réellement).

---

## Partie 3 — Les démarches consulaires : comment ça se passe aujourd'hui

### 3.1 Le parcours actuel, tel que le code le fait
1. **En ligne** (espace citoyen) : le citoyen choisit un service, remplit ses informations, dépose ses pièces (PDF/JPEG/PNG, 5 Mo). La plateforme signale les pièces manquantes.
2. **Examen** (personnel) : pièce par pièce (acceptée, refusée avec motif), puis décision : validée, à compléter, refusée. Email au citoyen. Historique et délai cible (si fixé) sont suivis.
3. **Au guichet** : le citoyen prend rendez-vous (créneaux et capacité), se présente, est enregistré. Le paiement est encaissé avec un reçu numéroté.
4. **Suivi du dossier au guichet** : déposé → en traitement → prêt à retirer → retiré, avec email à chaque étape.

### 3.2 Ce qui ne fonctionne pas bien
| # | Problème | Pourquoi c'est un problème | Amélioration proposée |
|---|---|---|---|
| D1 | **La pré-demande validée et le rendez-vous ne sont pas liés** | Le citoyen peut prendre rendez-vous sans que le guichet voie son dossier validé. Le personnel refait la vérification sur papier. | Lier le rendez-vous à la pré-demande validée ; à l'enregistrement, afficher la liste des originaux à présenter. |
| D2 | **Deux suivis distincts** : pré-demande (en ligne) et dossier (guichet) | Le citoyen ne voit pas une seule chronologie de sa démarche. | Une seule chronologie : soumission → validation → rendez-vous → traitement → prêt → retiré. |
| D3 | **Le paiement n'est pas proposé avant le rendez-vous** | Les frais sont payés au guichet seulement : file d'attente. Les montants existent (passeport 60 000 FCFA, carte 15 000, visa 50 000 dans les données de démonstration). | Afficher le montant et le mode de paiement dès la pré-demande. Un paiement en ligne suppose un prestataire de paiement : décision à prendre. |
| D4 | **Délai affiché en texte (« 4 à 6 semaines ») sans lien avec le délai suivi** | Le citoyen voit une durée qui n'est pas celle que le système calcule. | Un seul délai, saisi par service, affiché et suivi. |
| D5 | **Pas de suivi de la production du document** (passeport imprimé, carte éditée, expédition) | Impossible de dire « votre passeport est parti à Dakar ». | Étapes « en production » et « expédié » ; numéro de suivi si envoi. |
| D6 | **Pas de date d'expiration suivie** (carte consulaire, passeport) | Pas de rappel de renouvellement pour le citoyen. | Date de validité par document ; rappel par email avant échéance. |
| D7 | **Pas de règles pour les cas particuliers** | Passeport perdu (déclaration de perte), mineur sans parent présent, procuration à faire légaliser. | Liste des cas et pièce requise pour chacun, à fournir par le consulat. Je ne peux pas les inventer. |
| D8 | **Visa : seulement une liste de pièces** | Un visa dépend de la durée, de la finalité, de la validité du passeport (souvent 6 mois). Aucune de ces règles n'est modélisée. | Champs spécifiques au visa (finalité, durée, dates de séjour, validité du passeport) ; issue (accordé, refusé, valable jusqu'au). |
| D9 | **Légalisation et actes d'état civil** : pas de suivi des cachets ni des copies conformes | Il faut savoir quel document a été légalisé, à quelle date, avec quel numéro. | Registre des légalisations (numéro, date, agent) ; numérotation des copies. |
| D10 | **Personnes à charge** : le rattachement d'une démarche à un enfant ou à une personne à charge n'est pas vérifié | Une demande de passeport pour un mineur doit être liée au mineur et à son parent. | À vérifier dans le code avant de conclure ; puis lier la démarche à la personne à charge. |
| D11 | **Notifications uniquement par email** | Beaucoup de citoyens consultent surtout leur téléphone. | Canal SMS ou WhatsApp : décision et prestataire à choisir. |
| D12 | **Pas de mesure contre la file d'attente** | Les créneaux existent, mais aucune priorité pour les cas urgents (ex. laissez-passer) n'est prévue. | Règle de priorité, à définir par le consulat. |

### 3.3 Ce qu'il faut obtenir du consulat avant de développer les démarches
1. La liste complète des pièces par service, avec les cas (mineur, renouvellement, première demande).
2. Les délais réels par service (pour remplacer le texte fixe).
3. Les montants, les modes de paiement acceptés, et le prestataire de paiement si le paiement en ligne est voulu.
4. La règle pour un passeport perdu, un mineur sans parent présent, une procuration.
5. Les règles de visa : durée, validité du passeport exigée, issues possibles.
6. Qui valide les légalisations et comment elles sont numérotées.

---

## Partie 4 — Ordre de travail proposé

1. **Immédiat (sans décision lourde)** : D1 (lier pré-demande et rendez-vous), D2 (chronologie unique), D4 (délai unique). Ce sont des corrections de cohérence.
2. **Après réponses du consulat** : D3, D5, D6, D8, D9, D10.
3. **Après décision de l'association** : partie 2 (2FA, conflits, limite de mandats d'abord ; registre financier en dernier).
4. **Décisions de fond** : D11 (canal de notification), paiement en ligne (D3), commission électorale (1.4).

---

## Ce que cette analyse ne couvre pas
- Je n'ai pas testé les parcours visa et carte consulaire de bout en bout dans cette session : l'analyse vient de la lecture du code et des données de démonstration.
- Les montants et les délais cités viennent des données de démonstration du dépôt, pas d'une source officielle.
