# Règles d'abonnement des consulats et sécurité associée

Date : 15 novembre 2026. Ce document fixe les règles de bout en bout. **Statut : validées le 15 novembre 2026.** Les six points de la section 5 sont tranchés au comportement décrit (voir ci-dessous).

## 1. Principe : une date par consulat
Chaque consulat signe à sa propre date. Il n'existe donc aucune date commune de renouvellement.

- **Début** : la période démarre à la date du premier paiement enregistré (activation par le super admin ensuite).
- **Durée** : un an moins un jour, à partir de la date du paiement.
- **Prolongation** : si un paiement est enregistré avant l'échéance, la nouvelle période commence le lendemain de la fin en cours (pas de chevauchement).
- Données : `consulates.subscription_until` (fin de période), `consulate_payments` (historique).

## 2. Cycle de vie
| Étape | Règle | Où c'est codé |
|---|---|---|
| Sans paiement | consulat créé, inactif, aucun accès public au service | `ActivateConsulate` |
| Avis | avertissement 30 jours avant l'échéance | `SendSubscriptionNotices`, `consulat:send-subscription-reminders` (08h05) |
| Échéance dépassée | période de **grâce de 30 jours** : le consulat reste actif, marqué « en retard » | `subscriptionState()` |
| Fin de grâce | **suspension automatique** (`is_active = false`, `suspended_for_nonpayment = true`) | `consulat:check-subscriptions` (02h00) |
| Paiement après suspension | réactivation automatique à l'enregistrement du paiement | `ConsulatePayment::created` |

## 3. Qui fait quoi
- **Enregistrer un paiement** : super admin uniquement. Le consul ne voit que « Mon abonnement ».
- **Modifier une date d'abonnement** : personne depuis l'interface (les dates ne se saisissent qu'à travers un paiement).
- **Activer un consulat créé** : super admin, après le premier paiement.

## 4. Sécurité liée à l'abonnement
- **Renouvellement = nouvelle vérification** : tout paiement enregistré oublie les appareils de confiance du consulat. Le personnel et le consul ressaisissent le code à 6 chiffres.
- **Confiance d'un consul** : jusqu'à la fin de la période de son consulat. Un consulat sans abonnement valide n'a aucune confiance.
- **Super admin** (sans consulat) : confiance jusqu'au renouvellement annuel fixé dans `TWO_FACTOR_RENEWAL_DAY`.
- **Changement de mot de passe** : tous les appareils de confiance sont oubliés.
- **Double authentification obligatoire** pour les comptes qui gèrent élections, bureau et utilisateurs.
- **Récupération** : `consulat:reset-two-factor <email>`, accès serveur uniquement.

## 5. Décisions validées
1. Contrat à partir de la date de paiement (un an), sans date anniversaire commune.
2. Un paiement couvre une période ; pas d’échéancier pour l’instant.
3. Grâce de 30 jours.
4. Consulat suspendu : aucun accès (pas de lecture seule pour l’instant).
5. Montant et devise par défaut de la configuration ; pas de tarif par consulat pour l’instant.
6. Renouvellement de la confiance du super admin au 1er janvier.

## 5 bis. Points à réexaminer plus tard
1. **Période en cours d'année** : le contrat part de la date de paiement (un an). Acceptez-vous ce principe, ou voulez-vous une date anniversaire fixe pour tous ?
2. **Paiement en plusieurs fois** : aujourd'hui, un seul paiement couvre une période. Faut-il un échéancier ?
3. **Durée de grâce** : 30 jours aujourd'hui. Confirmez-vous ce délai ?
4. **Accès en suspension** : aujourd'hui, un consulat suspendu n'accède plus à rien. Faut-il un accès lecture seule pour exporter ses données ?
5. **Montant et devise** : valeurs par défaut dans la configuration (`SUBSCRIPTION_DEFAULT_AMOUNT`, XOF). Faut-il un tarif par consulat ?
6. **Super admin** : la date de renouvellement de sa confiance (1er janvier par défaut) reste-t-elle la bonne ?

## 6. Migrations et tests
Migration de cette étape : `2026_11_15_000037_two_factor_trusted_devices`. Tests : suite complète.

## 7. Mise à jour du 15 novembre 2026
- **Super admin** : aucun renouvellement automatique. Sa confiance d'appareil dure jusqu'à révocation manuelle (« Oublier tous les appareils ») ou changement de mot de passe.
- **Abonnements par mois de signature** : règle validée (chaque consulat part de sa date de paiement, un an moins un jour).
- **Vote du bureau pour un poste vacant** : après l'élection partielle, si le poste reste vacant, le bureau en exercice vote entre les membres actifs. Électorat figé à l'ouverture, un bulletin par personne, même règle de majorité absolue et de second tour.
- Tarif annuel retenu : **800 000 FCFA**, réajustable (`SUBSCRIPTION_DEFAULT_AMOUNT`).
- Migrations à lancer : `2026_11_15_000038_no_auto_renewal_and_board_vote`.
