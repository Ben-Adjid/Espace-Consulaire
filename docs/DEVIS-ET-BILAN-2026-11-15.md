# Devis indicatif et bilan — plateforme consulaire (15 novembre 2026)

**Important** : les montants ci-dessous sont des **estimations indicatives**. Je n'ai pas consulté les tarifs réels des prestataires (hébergement, noms de domaine, envoi d'emails, SMS). Avant d'envoyer un devis à un client, vérifie chaque ligne sur le site du prestataire. Les montants sont en **FCFA**, monnaie par défaut de la plateforme (`SUBSCRIPTION_DEFAULT_CURRENCY`).

## 1. Ce que couvre un client (un consulat)

| Poste | Nature | Base de calcul |
|---|---|---|
| Mise en service | ponctuel | paramétrage du consulat, services, créneaux, thème, comptes |
| Nom de domaine | annuel | renouvellement du domaine du consulat |
| Hébergement (serveur, base de données, sauvegardes) | annuel | serveur partagé entre consulats, sauvegardes quotidiennes |
| Certificat HTTPS | inclus | Let's Encrypt, renouvellement automatique |
| Envoi d'emails (SMTP) | annuel | selon le volume ; prévoir un prestataire transactionnel |
| SMS / WhatsApp (optionnel) | à l'usage | non encore intégré |
| Maintenance et mises à jour | annuel | corrections de sécurité, mises à jour Laravel et Next.js |
| Support | annuel | niveau à définir (heures ou délai de réponse) |
| Formation du personnel | ponctuel | séances avec le consul et l'agent |
| Reprise de données | ponctuel | si le consulat a déjà des fichiers |

## 2. Proposition de structure tarifaire

**A. Frais de mise en service (une fois)**
- Paramétrage, domaine, configuration, formation : fourchette indicative **150 000 à 300 000 FCFA** selon la taille du consulat (nombre de services, reprise de données).

**B. Abonnement annuel (par consulat)**
- Hébergement, domaine, emails, maintenance, support de base : fourchette indicative **240 000 à 480 000 FCFA par an**.
- Option « support renforcé » (délai de réponse court, interventions à distance) : **+100 000 FCFA par an** environ.
- Option SMS ou WhatsApp : **à l'usage**, facturé au prestataire avec une marge.

**C. Conditions de paiement**
- Paiement annuel, à la date de signature du contrat (la période démarre à ce moment-là, comme validé).
- Pas de mensualisation, conformément à ta décision.
- Renouvellement : même logique, à la date du paiement de l'année suivante.

## 3. Exemple chiffré (indicatif)

Consulat de taille moyenne, un an, hors options :

| Ligne | Montant indicatif (FCFA) |
|---|---|
| Mise en service (une fois) | 200 000 |
| Abonnement annuel de base | 360 000 |
| **Première année** | **560 000** |
| Années suivantes | 360 000 |

Avec le support renforcé : première année 660 000, puis 460 000 par an.

## 4. Points à vérifier avant tout devis
- Tarif réel de l'hébergement choisi, et capacité pour plusieurs consulats.
- Coût du nom de domaine dans l'extension choisie.
- Prestataire d'envoi d'emails et volume prévu.
- Qui signe pour le consulat (le consulat est une entité publique : la procédure de paiement peut être différente d'un paiement privé).
- Devise et TVA éventuelle.

## 5. Bilan A → Z

### 5.1 Ce qui est fait et testé
- **Démarches** : pré-demande en ligne, pièces, examen, rendez-vous, reçu, suivi au guichet, rattachement du dossier validé au rendez-vous, frais affichés, priorité urgente, cas particuliers signalés, date d'expiration des pièces et rappels.
- **Chronologie unique** du citoyen.
- **Messagerie** citoyen ↔ consulat.
- **Association** : bureau, élections hors ligne, élections en ligne (candidatures, vote anonyme, second tour, départage, proclamation automatique), commission électorale, élection partielle pour les postes vacants, conflits d'intérêts, double contrôle des pièces (président, trésorier), mandats consécutifs, plafond de clôture, registre financier (cotisations, dépenses approuvées par un autre membre), export public.
- **Abonnements** : période par consulat à partir de la date de paiement, grâce, avertissement, suspension automatique, réactivation au paiement.
- **Sécurité** : double authentification obligatoire pour les comptes qui gèrent élections, bureau et utilisateurs ; appareils de confiance ; récupération par un opérateur ; rôles et permissions par consulat.
- **Multilingue** : français, anglais, arabe, avec RTL, pour les pages, l'administration et les emails.
- **Tests** : 332 tests automatiques réussis. ESLint sans erreur. Build frontend réussi.

### 5.2 Ce qui est fait mais pas vérifié à l'écran
- Second tour et départage des élections en ligne.
- Registre financier, chronologie, rappels : testés côté API et logique, pas contrôlés visuellement.
- Commission électorale et attestations.

### 5.3 Ce qui reste à améliorer
- **Visa, carte consulaire, cas particuliers** : règles et pièces à fournir par chaque consulat.
- **Paiement en ligne** et **SMS / WhatsApp** : prestataire à choisir.
- **Super admin** : sa confiance expire au 1er janvier ; à revoir (voir 6).
- **Interface** : registre financier et élections à tester avec des utilisateurs réels.
- **Vote du bureau** pour un poste vacant après l'élection partielle : non construit.
- **Conflits d'intérêts** : seul l'email identique est détecté ; les liens familiaux ne le sont pas.
- **Titres de pages** et textes d'administration : quelques libellés restent en français.
- **Dette technique** : 12 avertissements ESLint à traiter.

### 5.4 Ce qui reste à décider
- Tarifs (section 2 et 3).
- Renouvellement de la confiance du super admin.
- Consulat suspendu : accès en lecture ou non.
- Paiement en plusieurs fois : non.

### 5.5 Avant la mise en production
- Lancer toutes les migrations de `000029` à `000037`.
- Configurer le SMTP.
- Changer les identifiants du super admin de démonstration.
- Vérifier les parcours à l'écran, en particulier le vote et le registre financier.
- Commit et déploiement : seulement sur ton accord.

## 6. Ta question : la confiance du super admin

Le super admin n'appartient à aucun consulat, donc il n'a pas de date d'abonnement. Pour lui, j'ai mis une règle simple : **la confiance de son navigateur expire le 1er janvier de chaque année**. Il ressaisit alors le code à 6 chiffres une fois par an. Si tu préfères, on peut fixer une autre date (par exemple l'anniversaire de ta propre plateforme), ou décider qu'il n'y a pas de renouvellement automatique et que seul le bouton « Oublier tous les appareils » révoque la confiance.

## 7. Abonnement qui commence en novembre, décembre, etc.

Le système gère déjà ce cas : chaque consulat a sa propre date, qui démarre le jour du paiement et dure un an moins un jour. Un client qui s'abonne le 15 novembre 2026 a sa période jusqu'au 14 novembre 2027. Il n'y a aucun mois fixe. Le même principe s'applique à la confiance des appareils : elle expire à la fin de la période de **ce** consulat, et un nouveau paiement oublie les appareils.

## 8. Mise à jour du tarif (15 novembre 2026)
- **Abonnement annuel retenu : 800 000 FCFA par an**, par consulat, réajustable avec le temps.
- Ce montant couvre l'hébergement, le nom de domaine, les emails, la maintenance et le support de base (à confirmer dans le contrat). La mise en service reste à chiffrer séparément.
- Tous les montants des sections 2 et 3 sont remplacés par cette décision ; le détail des coûts reste à vérifier auprès des prestataires.
