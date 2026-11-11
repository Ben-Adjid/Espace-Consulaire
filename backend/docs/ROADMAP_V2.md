# Feuille de route V2

Fonctionnalités prévues. Elles sont **absentes de la démo** ; seule leur structure existe :
drapeaux dans `config/features.php` (désactivés) et contrats dans `app/Contracts/`.

| Module | État dans la démo | Prochaine étape |
|---|---|---|
| Pré-demande en ligne + upload de documents | contrat `AppointmentPrerequestRepository` | table `prerequests`, stockage privé (disque `local`), antivirus à l'upload |
| Suivi de dossier | drapeau `case_tracking` | statuts de dossier + page de suivi par référence |
| Paiements mobile money | contrat `PaymentGateway` | intégration d'un agrégateur en mode test, webhooks signés |
| SMS / WhatsApp | contrat `NotificationChannel` | canal Notification Laravel + API WhatsApp Business |
| Immatriculation consulaire | drapeau `consular_registration` | formulaire d'état civil + validation par agent |
| Alertes d'urgence | drapeau `emergency_alerts` | bandeau global piloté par le super_admin, diffusion par SMS |

Points d'attention avant la mise en production : vraies données personnelles (RGPD / loi sénégalaise
sur les données personnelles), hébergement, sauvegardes, authentification forte et validation opérationnelle.

## Propositions complémentaires

Ces idées sont distinctes des modules V2 listés plus haut et ne sont pas activées par défaut :

| Proposition | Valeur | Garde-fou / dépendance |
|---|---|---|
| Replanification autonome d'un rendez-vous | Réduit les appels et libère les créneaux annulés | Jeton personnel, contrôle de capacité atomique, règles de délai |
| Liste d'attente par service | Remplit les créneaux libérés | Consentement séparé, durée de conservation et quotas |
| Notifications multicanales | Rappels plus accessibles dans les zones où l'email est peu utilisé | Consentement, coût par message, fournisseurs et règles locales |
| Tableau de bord comparatif | Aide les équipes à anticiper les périodes de forte demande | Agrégation/anonymisation et permissions multi-consulats |
| Mode faible débit et accessibilité | Facilite l'accès sur mobile et connexions instables | Audit WCAG, tests réels en RTL et appareils modestes |
| Gestion éditoriale des traductions | Aide à repérer les contenus non traduits avant publication | Repli explicite en français et validation humaine |

## Stabilisation de l'existant

Le journal d'activité d'administration est disponible : les entrées consignent l'acteur, le consulat, l'action,
l'objet et les noms des champs modifiés, sans leurs valeurs. Restent à compléter avant mise en production :
authentification à deux facteurs, tests de bout en bout navigateur, SMTP, validation sur MySQL et procédures
de sauvegarde/restauration. Les tâches exécutées et les idées proposées sont suivies dans `feature_backlog`.
