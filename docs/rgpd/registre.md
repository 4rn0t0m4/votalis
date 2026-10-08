# Registre des activités de traitement (projet)

Document interne au sens de l'article 30 du RGPD. **Projet à faire valider par un juriste ou un délégué à la protection des données** avant l'ouverture publique. Les champs entre crochets sont à compléter (voir `LEGAL_*` dans `.env.example`).

Responsable de traitement : [nom, forme juridique, adresse]. Contact : [e-mail]. Délégué à la protection des données : [le cas échéant].

## Traitement 1 — Comptes participants

| Rubrique | Contenu |
| --- | --- |
| Finalités | Permettre de voter, argumenter, proposer et arbitrer ; garantir un compte par personne ; sécuriser l'accès |
| Base légale | Exécution du contrat (conditions d'utilisation) ; consentement explicite pour les votes et contributions (art. 9 §2 a), recueilli à l'inscription (`consented_at`) |
| Personnes concernées | Participants inscrits |
| Données | Pseudonyme ; e-mail (chiffré, cast `encrypted`) et haché HMAC séparé (`email_hash`) ; mot de passe (Argon2id) ; secret TOTP et codes de secours (chiffrés) ; clés WebAuthn ; rôle ; dates de création, de consentement, de vérification, de dernière visite (jour) |
| Destinataires | Équipe d'exploitation (administrateur technique) ; hébergeur (UE) ; fournisseur d'envoi d'e-mails (UE) |
| Transferts hors UE | Aucun |
| Conservation | Durée du compte ; suppression en libre-service ; préavis à 35 mois d'inactivité puis suppression à 36 mois (`accounts:purge-inactive`) |
| Sécurité | Chiffrement de l'e-mail, hachage des secrets, 2FA obligatoire pour les rôles privilégiés, CSP stricte, HSTS, journaux sans donnée personnelle, sauvegardes chiffrées |

## Traitement 2 — Votes, conditions et réponses aux arbitrages

| Rubrique | Contenu |
| --- | --- |
| Finalités | Calculer les résultats par proposition, les classements publics et les résultats d'arbitrage |
| Base légale | Consentement explicite (données révélant des opinions politiques, art. 9) |
| Données | Identifiant interne du compte, proposition, deux réponses (−1/0/1), condition libre (200 caractères), vote initial, indicateur « révisé après lecture des arguments », combinaisons d'arbitrage, horodatages |
| Destinataires | Public : agrégats seulement. Modérateurs : jamais les votes individuels |
| Conservation | Durée du compte ; effacés à la suppression (cascade), compteurs recalculés |
| Mesures | Séparation : jamais liés à l'e-mail ; service d'embeddings et exports ne reçoivent que des identifiants internes |

## Traitement 3 — Propositions, arguments et révisions

| Rubrique | Contenu |
| --- | --- |
| Finalités | Alimenter le débat public ; historique public des modifications |
| Base légale | Consentement explicite ; intérêt légitime pour la conservation après suppression du compte (intégrité du débat : d'autres participants ont voté et argumenté) |
| Données | Texte des fiches et arguments, sources, horodatages, auteur (identifiant interne, affiché par pseudonyme) |
| Conservation | Illimitée ; à la suppression du compte, `author_id` passe à null (« participant supprimé ») |

## Traitement 4 — Modération, signalements et contestations

| Rubrique | Contenu |
| --- | --- |
| Finalités | Faire respecter la charte ; journal public immuable ; contestation ; transparence trimestrielle |
| Base légale | Intérêt légitime (intégrité du débat) ; obligation légale pour les contenus illicites |
| Données | Signalements (motif, précision, signaleur), décisions (action, motif, identifiant interne de l'acteur, rôle), contestations (texte, issue, motivation), suspensions |
| Destinataires | Public : journal sans identité ni contenu illégal ; modérateurs : historique agrégé des signaleurs, jamais leur identité |
| Conservation | Journal : illimitée (ajout seul). Signalements et contestations : conservés sans auteur ni texte libre après suppression du compte |

## Traitement 5 — Signaux d'intégrité

| Rubrique | Contenu |
| --- | --- |
| Finalités | Détecter des opérations coordonnées pour examen humain |
| Base légale | Intérêt légitime (intégrité du débat) |
| Données | Identifiants internes de comptes et de propositions, comptages, similarités ; seuils en configuration privée |
| Destinataires | Modération, comité, administrateur (lecture) ; jamais public |
| Conservation | Illimitée (agrégats) ; aucune décision automatique |

## Traitement 6 — Journaux techniques

| Rubrique | Contenu |
| --- | --- |
| Données | Journaux applicatifs JSON nettoyés (`ScrubPersonalData`) : ni e-mail ni IP ; journaux du reverse proxy sans adresse IP (`log_format sans_ip`) |
| Conservation | [à fixer avec l'hébergeur, 6 mois maximum] |
