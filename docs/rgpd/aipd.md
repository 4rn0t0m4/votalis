# Analyse d'impact relative à la protection des données (projet)

Obligatoire (art. 35 RGPD) : traitement à grande échelle de données révélant des opinions politiques. **Projet à faire valider par un juriste ou un délégué à la protection des données.** Méthode : grille CNIL (contexte, principes fondamentaux, risques, mesures).

## 1. Contexte

- **Traitement** : plateforme de débat citoyen permettant de voter, argumenter, proposer et arbitrer des mesures publiques.
- **Responsable** : [à compléter]. **Sous-traitants** : hébergeur européen [à compléter], fournisseur d'e-mails européen [à compléter].
- **Données** : pseudonyme, e-mail chiffré, secrets d'authentification, votes et conditions, contributions, signalements, contestations, date de dernière visite. Aucune donnée d'identité civile, de localisation ni de profil.
- **Personnes** : toute personne majeure disposant d'un e-mail ; cible MVP 10 000 visiteurs par jour.
- **Cycle de vie** : inscription (consentement explicite) → contributions → suppression en libre-service ou purge après 36 mois d'inactivité.

## 2. Principes fondamentaux

| Principe | Mise en œuvre |
| --- | --- |
| Finalités déterminées | Débat, résultats agrégés, modération, transparence ; aucune finalité publicitaire ni de profilage |
| Minimisation | Quatre données d'identification au plus ; pas d'IP ni de journal de connexion ; date de visite au jour près |
| Exactitude | Pseudonyme et e-mail modifiables ; propositions corrigeables ; historique public |
| Conservation limitée | Suppression libre-service ; purge d'inactivité avec préavis ; purge des jetons |
| Information | Politique de confidentialité, mentions de consentement, charte, journal public, rapports de transparence |
| Droits | Export JSON et suppression en libre-service ; autres droits par e-mail ; réclamation CNIL |
| Sous-traitance | Hébergement et e-mail en UE, hors Cloud Act ; aucun transfert |

## 3. Risques

| Risque | Sources | Gravité | Vraisemblance | Mesures |
| --- | --- | --- | --- | --- |
| Accès illégitime aux votes (réidentification d'opinions politiques) | Intrusion, fuite de sauvegarde, abus interne | Importante | Limitée | Votes liés à un identifiant interne ; e-mail chiffré avec clé distincte (`EMAIL_HASH_KEY`, `APP_KEY`) ; sauvegardes chiffrées ; 2FA obligatoire pour les rôles privilégiés ; les modérateurs ne voient jamais les votes ; audit externe avant ouverture |
| Modification non désirée (manipulation des résultats) | Comptes multiples, campagne coordonnée | Importante | Significative | Un compte par e-mail vérifié, domaines jetables refusés, plafonds, limitation de débit, signaux d'intégrité examinés par des humains, journal public, suspension contestable |
| Disparition de données | Panne, erreur, attaque | Limitée | Limitée | Sauvegardes chiffrées quotidiennes, copie hors ligne, test de restauration mensuel, journal de modération en ajout seul |
| Atteinte aux auteurs par la modération | Décision arbitraire, exposition d'un contenu illégal | Limitée | Limitée | Motifs fermés, journal immuable sans identité, contestation tranchée par un tiers, contenu illégal jamais reproduit |
| Divulgation de l'identité d'un signaleur | Interface de modération | Limitée | Limitée | Historique agrégé seulement, jamais de pseudonyme ni d'identifiant du signaleur |

## 4. Mesures complémentaires et décisions

- Avant ouverture : audit de sécurité externe (ASVS niveau 2), audit RGAA, validation juridique des documents, désignation du contact RGPD.
- Après ouverture : revue annuelle de cette analyse ; revue des seuils d'intégrité ; vérification des sous-traitants.
- Points d'attention : la vérification téléphonique (V2) ajoutera une donnée ; le consensus par familles de votants (V2) devra être analysé pour le risque de réidentification de groupes.

Avis du délégué à la protection des données : [à compléter]. Décision du responsable de traitement : [à compléter].
