# Captures de test (données de démonstration, base locale)

Dossiers : 01-super-admin, 02-consul-agent, 03-citoyen, 04-association, 05-bilan.

Attention avant publication :
- Les consulats affichés (Tchad, Ghana, Togo…) sont des données fictives de démonstration, mais portent des noms de pays réels. Ajoutez la mention « démonstration » dans la publication.
- Ne publiez jamais une capture de la page « Sécurité du compte » : elle affiche des codes de secours.
- Les noms et numéros de téléphone (Jean Exemple, +221 77 100 00 0x) sont fictifs.

## Limites de cette campagne
- Parcours couverts : super administrateur, consul, citoyen (inscription, confirmation, démarche en brouillon, envoi bloqué), président d'association (activité, adhérent).
- Non couverts : élections (candidature, vote, résultats), agents et secrétariat, autres fonctions du bureau, envoi complet d'une démarche (le téléversement de fichiers n'est pas disponible dans l'outil de navigation).
- Le journal du bureau et l'export CSV n'ont pas d'écran : ils existent seulement côté API.

## Mise à jour (agent, consul, association)
- Agent : file de travail, pré-demandes, examen piece par piece (captures 02-consul-agent/06 à 13).
- Consul : reconnaissance officielle de l'association de démonstration, onglet élections, formulaire de nouvelle élection (04-association/03 à 07).
- Non fait : création complète d'une élection, candidature, vote, résultats, espace du président élu.

## Élections (fait)
- Création par le consul, candidature d'un citoyen membre actif, validation de la candidature, vote anonyme, proclamation, espace du bureau du nouvel élu (04-association/08 à 14).
- Méthode de test : les dates du scrutin ont été avancées en base locale, puis la commande réelle `consulat:run-elections` a été lancée. Ce n'est pas un parcours réel dans le temps.
- Ne publiez pas la capture du reçu de vote : elle n'a volontairement pas été prise.
- Non fait : vérification du reçu, second tour, contestation, espace complet du président élu (le test a élu un responsable de la communication).

## Corrections et compléments (dernier tour)
- Création d'élection : test automatisé ajouté (AssociationAdminFormTest). Le cas en ligne passe côté serveur. La non-reproduction dans le navigateur vient de l'outil de saisie des dates, pas de l'application.
- Reconnaissance officielle : test ajouté (AssociationRecognitionTest). La sauvegarde fonctionne ; mon clic précédent n'avait pas atteint le bouton « Sauvegarder ».
- Message de la phase candidature corrigé côté citoyen (« Vote ouvert à partir du » pendant les candidatures, « Vote ouvert depuis le » ensuite).
- Présidence perdue après élection : comportement voulu, une élection complète remplace le bureau.
- Pages du citoyen se chargeant lentement sous navigation rapide : vérifiées une à une après attente.
- Pages sans capture : feature backlog (pas de route admin), écrans de sécurité (codes de secours).
