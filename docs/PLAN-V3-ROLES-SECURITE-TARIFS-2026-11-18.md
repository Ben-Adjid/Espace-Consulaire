# Plan v3 : rôles, sécurité de la base, tarifs, règles consulaires

Date : 18 novembre 2026. Je n'ai pas d'expérience personnelle en ambassade, en consulat ou en entreprise, et je ne peux pas valider la demande du marché. Ce document distingue ce qui est codé et vérifié, ce qui est proposé, et ce qui doit être décidé par toi, le consulat ou l'association.

---

## 1. Ce qui est codé et vérifié aujourd'hui

- **Scellé de scrutin** (`consulat:seal-election`) : après proclamation, produit un fichier avec les résultats, l'empreinte finale du journal des votes et sa propre empreinte. À publier hors plateforme. Vérifié par un test.
- **Registre des adhérents en lecture** : réservé aux membres du bureau en exercice, sans aucun contact (email, téléphone) ni note. Vérifié par un test.
- **Déclaration de lien familial** à la candidature, enregistrée.
- **Rapprochement reçu / démarche** : un reçu valide par rendez-vous, une démarche payée une fois.
- **Carte consulaire** : deux variantes, pièces et tarifs confirmés.
- **Suite de tests** : passe, ESLint à 10 avertissements.

## 2. La base de données et l'administrateur : la meilleure solution réaliste

**Principe** : un administrateur qui contrôle le serveur ET la base peut toujours tout lire. La seule façon de l'empêcher est de **ne pas lui donner la clé**, et de rendre toute altération visible par des tiers.

**Niveau 1 — déjà en place** : bulletins et votes séparés ; journal chaîné ; reçu par électeur ; scellé à publier.

**Niveau 2 — à faire (peu coûteux)** :
- Publication du scellé **avant** toute consultation des résultats (affichage au siège, envoi à la commission).
- Journal d'accès à la base hors de la base (serveur de journaux distinct, en lecture seule).
- Deux administrateurs distincts : l'un gère les applications, l'autre les bases. Aucun ne peut seul changer un résultat.

**Niveau 3 — à faire avant la première élection réelle** : chiffrement des choix de vote, dont la clé est partagée entre trois membres de la commission (seuil de deux sur trois pour dépouiller). Un administrateur seul ne voit que des données chiffrées. Cela demande un chantier dédié de quelques jours avec un test de cryptographie revu par un tiers.

**Ce qu'aucun logiciel ne garantit seul** : la confiance absolue. Elle vient de la séparation des pouvoirs entre personnes et de la publication externe.

## 3. Le président et le bureau : ce qu'ils peuvent faire

**Président (et chaque membre du bureau en exercice)**
- Voir le registre des adhérents (nom, catégorie, statut) : fait.
- Proposer et approuver des dépenses, voir le solde : fait.
- Être candidat, voter, voir les résultats publiés : fait.

**Ce qui manque au président**
- Publier des activités et mettre à jour la description publique de l'association : aujourd'hui réservé à l'administrateur.
- Convoquer les adhérents à une assemblée ou à une élection (email automatique) : à faire.
- Produire un procès-verbal signable à la clôture : à faire.
- Tableau de bord : mandats, échéances, scrutins en cours, cotisations en retard.

## 4. Fonctions par rôle au sein de l'association (proposition)

| Rôle | Ce qu'il doit pouvoir faire | Déjà fait | À faire |
|---|---|---|---|
| **Président** | voir le registre, convoquer, publier, tableau de bord | registre, dépenses, candidature | publication, convocation, PV, tableau de bord |
| **Vice-président** | remplacer le président, suivre les activités | voir le registre | délégation formelle, historique des actions déléguées |
| **Secrétaire général** | procès-verbaux, correspondance, registre des adhérents | registre en lecture | rédaction et archivage des PV, modèles de courriers |
| **Trésorier** | cotisations, dépenses proposées, solde, rapport financier | cotisations, dépenses, solde | rapport financier trimestriel, rappel des cotisations en retard |
| **Responsable des activités (sport, culture)** | publier et gérer les activités | — | droit de publication d'activités limité à son domaine |
| **Responsable communication** | annonces publiques, actualités de l'association | — | publication d'annonces (modération par le président) |
| **Responsable des relations extérieures** | contacts et partenariats | — | carnet de contacts interne (sans exposition publique) |
| **Adhérent simple** | voir activités, résultats, solde, son reçu de vote, s'inscrire | la plupart | demande d'adhésion en ligne, signalement au bureau |

**Principe de conception** : chaque rôle a des droits précis, rien de plus. Un responsable d'activités ne peut pas toucher aux finances ; le trésorier ne peut pas publier d'annonces. Chaque action est journalisée.

## 5. Agents du consulat et secrétariat

| Rôle | Ce qu'il doit pouvoir faire | Déjà fait | À faire |
|---|---|---|---|
| **Consul** | valider les comptes, décider des dossiers, gérer l'équipe, voir les rapports | la plupart | validation groupée, tableau de bord de charge |
| **Agent de guichet** | accueillir, enregistrer, encaisser, examiner les pièces | la plupart | actions groupées pour les cas simples |
| **Secrétaire du consulat** | rendez-vous, correspondance, saisie, messagerie, modèles de réponses | messagerie, modèles | rôle dédié « secrétariat » sans droit d'encaissement ni de décision |
| **Responsable des finances du consulat** | reçus, rapprochement, rapport mensuel | reçus, rapport | rapprochement automatique reçu / démarche (partiel) |

**Recommandation** : créer le rôle **secrétariat** (sans encaissement ni décision) : c'est la séparation la plus utile au quotidien.

## 6. Améliorations pour les agents

- **Aide intégrée** : le site dispose déjà d'un assistant (chat). Le faire répondre aux questions internes (procédures, rôles) est une amélioration simple.
- **Notices illustrées** par écran, en trois langues.
- **Raccourcis** : recherche par numéro de reçu, de dossier ou de référence de rendez-vous, au guichet.
- **Alertes** : dossier en retard, reçu annulé, tentative de doublon.

## 7. Tarif : ce qui se passe réellement

**Ce que je peux affirmer** : 800 000 FCFA par an ne couvre pas la maintenance continue. Un tarif entre 1 200 000 et 2 400 000 FCFA par an est un ordre de grandeur réaliste, à confirmer par des coûts réels.

**Ce que je ne peux pas affirmer** : que des clients accepteront ce prix. Il n'existe pas de données de marché dans ce projet, et je n'ai pas de contacts commerciaux.

**Méthode recommandée** :
1. Proposer le tarif à 2 ou 3 consulats pilotes, avec une lettre d'intention.
2. Mesurer le taux d'acceptation et les objections.
3. Ajuster. Le tarif reste modifiable : chaque nouveau tarif prend effet à sa date (principe déjà en place).
4. Un contrat annuel payé d'avance, avec mise en service séparée.

## 8. Authentification de diplôme : le point de vue logique

Dans une logique de service consulaire, le prix reflète le **travail réel** :
- **500 FCFA** : si le consulat vérifie seulement l'apposition d'un cachet ou d'une signature déjà authentifiée par l'autorité d'origine.
- **2 000 FCFA** : si le consulat doit vérifier auprès de l'autorité émettrice ou faire un contrôle supplémentaire.

Je ne peux pas dire quel est le tarif « normal » dans ton pays : cela dépend des textes du consulat. **Ma recommandation** : fixer le tarif selon le travail effectivement fait, et le mettre dans la fiche du service (le système le permet, avec une date d'effet).

## 9. Visa, bonne moralité, cas particuliers : propositions à valider

Ces règles sont des propositions. Le consulat doit les confirmer.

- **Visa** : passeport valide au moins six mois après le départ (à confirmer) ; contrôle automatique de cette validité à partir de la date d'expiration du passeport ; motif, dates et hébergement dans le formulaire.
- **Certificat de bonne moralité** : pièce d'identité en cours de validité, photocopie, justificatif de résidence, présence au guichet pour la photo et l'empreinte. Tarif à fixer.
- **Cas particuliers** : passeport perdu (déclaration de perte), mineur sans parent (autorisation signée et pièce du parent), procuration (acte notarié). Chaque cas impose une pièce, validée par le consulat.

## 10. Sécurité et droits du personnel

**Principe du moindre privilège** : chaque compte n'a que les droits de son rôle. Un agent ne voit que son consulat ; un membre du bureau ne voit pas les contacts des adhérents ; un administrateur de plateforme n'accède pas aux dossiers des citoyens sans motif journalisé.

**Ce qui est en place** : cloisonnement par consulat ; permissions par rôle ; double authentification obligatoire pour les comptes sensibles ; journal des actions ; registre sans contact.

**Ce qu'il faut ajouter** :
- Journal de consultation des dossiers personnels (qui, quand, quel dossier).
- Revue trimestrielle des droits.
- Déconnexion automatique après inactivité.
- Séparation des rôles administrateur (applications et bases).
- Politique écrite d'accès aux données, signée par chaque personne qui a accès.
- Test d'intrusion par un tiers avant la mise en production.

## 11. Pour la SAS

- **Contrat par consulat** : durée, prix, périmètre, niveau de service, responsabilité limitée, restitution des données.
- **Sous-traitance des données** : une convention écrite, les responsabilités de chacun.
- **Assurance** responsabilité civile professionnelle.
- **Juriste** pour valider la répartition des rôles (la SAS n'encaisse jamais les frais consulaires).
- **Support** : niveau de service écrit, heures, délais de réponse.

## 12. Décisions à prendre

1. Chiffrement des votes (niveau 3) : acceptes-tu le chantier et la contrainte des trois membres de la commission ?
2. Tarif : pilote avec 2 ou 3 consulats, à quel montant ?
3. Diplôme : 500 ou 2 000 FCFA, selon le travail réel ?
4. Visa, bonne moralité, cas particuliers : pièces et délais, validés par le consulat ?
5. Rôle « secrétariat » au consulat : on le crée ?
6. Rôles de l'association : les fonctions du tableau 4 te conviennent-elles ?

## 13. Statut d'implémentation (mise à jour du 2026-11-18)

- **Rôles et droits du bureau (sections 3 et 4)** : fonctions et droits codés dans `BoardPermissions`, API du bureau en place, page « Espace du bureau » compilée. Test navigateur avec un compte de bureau : à faire.
- **Secrétariat et agents (sections 5 et 10)** : rôle secrétariat, droits séparés, journal d'accès aux pièces. Interface secrétariat : non testée dans le navigateur.
- **Visa (section 9)** : validité minimale du passeport codée et contrôlée. Règles par catégorie : à valider par le consulat.
- **Authentification de diplôme (section 8)** : frais non tranchés (500 ou 2 000 FCFA), non codé.
- **Bonne moralité et cas particuliers (section 9)** : pièces et tarifs à valider, non codés.
- **Tarif (section 7)** : 800 000 FCFA par an retenu pour l'instant, 1,5 M à trancher.
- **Base de données et administrateur (section 2)** : chiffrement des bulletins par clés partagées entre 3 membres, non codé (décision et travail cryptographique requis).
- **Production (sections 11 et 12)** : rien d'exécuté ni déployé, en attente d'autorisation explicite.
