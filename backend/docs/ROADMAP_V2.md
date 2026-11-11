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
sur les données personnelles), hébergement, sauvegardes, journalisation d'audit des actions admin.
