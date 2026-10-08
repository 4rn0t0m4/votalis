# Plan du lot 3 — Participation

Statut : **en attente de validation** (rédigé le 8 octobre 2026).
Référence : cahier des charges, sections 4.3, 4.5, 4.7, 4.9, 4.10, 9, 10, 12 et 13. Branche : `lot-3-participation`.

## 1. Objectif et critères d'acceptation

Le lot 3 ouvre la participation : double vote avec conditions, vote rapide, module d'arbitrages, classements multiples, plafonds de votes et détection de doublons au dépôt. C'est le lot le plus lourd du MVP : il introduit le service Python d'embeddings et Meilisearch. Je propose de le réaliser en trois phases sur la même branche, avec un point d'étape à la fin de chacune, et une seule pull request à la fin.

| # | Critère (section 13) | Vérification |
| --- | --- | --- |
| C1 | Le dépassement d'un plafond est refusé côté serveur, pas seulement dans l'interface | Tests : 301e vote du jour refusé par le service même en appelant directement l'action Livewire ; idem propositions et arguments (déjà en place) |
| C2 | « Baisser les charges des PME » suggère « Alléger les cotisations des petites entreprises » | Test d'intégration avec le service d'embeddings réel sur ce couple de phrases, seuil par défaut ; un test unitaire avec embeddings simulés couvre la logique sans dépendre du modèle |

Critères complémentaires proposés :

| # | Critère | Vérification |
| --- | --- | --- |
| C3 | Un vote par compte et par proposition ; le vote initial est conservé quand le vote est révisé | Tests : clé composite, révision, `desirable_initial` et `necessary_initial` inchangés |
| C4 | Les résultats détaillés d'une fiche n'apparaissent qu'après le vote du participant | Tests : visiteur et participant non votant ne voient pas les pourcentages ; après vote, oui |
| C5 | Le premier vote verrouille le fond de la fiche | Test : `content_locked_at` posé, la modification de fond refusée ensuite |
| C6 | Un arbitrage ne se valide que si la contrainte est atteinte, et seule la dernière combinaison compte | Tests côté serveur sur la validation et sur le remplacement de la réponse |
| C7 | Une mesure sans chiffrage n'entre pas dans un arbitrage | Test : ajout refusé si `impact` ou `source_url` manque |
| C8 | Aucun texte de proposition ne sort du réseau privé | Le service d'embeddings est auto-hébergé ; test : la configuration refuse une URL hors du réseau Docker en production (liste d'hôtes autorisés) |
| C9 | Aucun classement unique par soutien | Revue : les onglets ne proposent jamais un tri par nombre de « oui » |

## 2. Périmètre

Phase A — Votes et classements :

- Double vote souhaitable / nécessaire (oui, non, je ne sais pas), condition « oui, à condition que… » (200 caractères), vote révisable, vote initial conservé, verrou de la fiche, plafond de 300 votes par jour, pas de vote sur sa propre proposition.
- Vote rapide : une proposition à la fois, ordre partiellement aléatoire favorisant les propositions récentes et peu votées, arguments repliés, passage à la suivante.
- Résultats détaillés sur la fiche après le vote du participant ; conditions regroupées et affichées.
- Classements par thème en onglets : les plus débattues, les plus clivantes, les nécessaires mais pas souhaitées, celles dont le soutien progresse après lecture des arguments, les plus choisies dans les arbitrages. L'onglet « consensuelles » est affiché comme « à venir ».

Phase B — Doublons et recherche :

- Service Python `consensus/` (FastAPI) exposant `POST /embed` avec un modèle multilingual-e5 auto-hébergé ; Laravel l'appelle uniquement via le réseau Docker interne.
- Colonne `embedding vector` sur `proposals`, calculée à la création et à chaque révision de contenu, par une tâche en file (Redis) ; index HNSW pgvector.
- Détection de doublons pendant la rédaction : après saisie du titre et de la mesure, les 5 fiches les plus proches au-dessus du seuil, avec trois actions : soutenir (aller voter), proposer une variante (V2 : désactivé avec explication), déposer quand même.
- Regroupement des conditions par similarité d'embeddings (seuil configurable) pour l'affichage sur la fiche.
- Meilisearch : indexation des propositions (titre, problème, mesure, thème), recherche plein texte en français sur `/recherche` et dans la page de thème.

Phase C — Arbitrages :

- Modèle : `tradeoffs` (titre, objectif, contrainte, unité, statut, source), `tradeoff_items` (proposition, impact, incertitude, source), `tradeoff_answers` (participant, items choisis, conditions, modifié le).
- Administration par le comité éditorial : créer l'exercice, ajouter des mesures candidates chiffrées et sourcées, publier, clore. Les participants proposent des mesures candidates ; le comité les ajoute après vérification du chiffrage.
- Exercice participant : composer sa combinaison, jauge mise à jour en direct (Livewire), arguments pour et contre de chaque mesure, « acceptée à condition que… », validation seulement si la contrainte est atteinte, réponse remplaçable, historique visible par le participant.
- Résultats publics par exercice : fréquence de choix de chaque mesure, combinaisons les plus fréquentes, conditions les plus citées.

Reporté : consensus et familles de votants (V2, le service Python est posé pour l'accueillir), familles et variantes (V2), SMS (V2), signalement et modération (lot 4), export open data (V3).

## 3. Modèle de données

| Table | Champs | Remarques |
| --- | --- | --- |
| votes | participant_id, proposal_id, desirable (−1/0/1), necessary (−1/0/1), condition (200, nullable), desirable_initial, necessary_initial, revised_after_arguments (bool), created_at, updated_at | Clé primaire composite ; `participant_id` = identifiant interne du compte, jamais l'e-mail ; contraintes CHECK sur les valeurs |
| proposals (ajouts) | embedding vector(384), embedding_version, votes_count, arguments_count, cached rankings | Index HNSW (`vector_cosine_ops`) ; compteurs dénormalisés tenus par le service de vote |
| vote_conditions_groups | proposal_id, label, count, condition_ids (JSON), computed_at | Résultat du regroupement, recalculé en file à chaque nouvelle condition |
| tradeoffs | id, title, slug, objective, constraint_value, unit, direction (`at_least`, `at_most`), status (`draft`, `open`, `closed`), source_url, theme_id (nullable), timestamps | |
| tradeoff_items | id, tradeoff_id, proposal_id, impact (decimal), uncertainty (texte court), source_url, position | `impact` et `source_url` obligatoires |
| tradeoff_answers | participant_id, tradeoff_id, item_ids (JSON), conditions (JSON item_id → texte), total, updated_at | Clé composite ; la réponse est remplacée, et un historique `tradeoff_answer_revisions` conserve les combinaisons précédentes, visibles par le participant seul |
| tradeoff_suggestions | id, tradeoff_id, participant_id, proposal_id, note, status | Mesures candidates proposées par les participants, traitées par le comité |

## 4. Règles métier côté serveur

- `VoteService::cast(User, Proposal, desirable, necessary, condition, fromArguments)` : interdit sur sa propre proposition et sur une fiche non publiée ; plafond 300 par jour (moitié pour les comptes récents) via `ContributionCaps` ; premier vote → `desirable_initial`, `necessary_initial`, `content_locked_at` sur la fiche ; révision depuis la fiche (arguments visibles) → `revised_after_arguments`.
- `QuickVoteSelector` : prochaine proposition non encore votée par le participant, tirée au sort parmi les fiches publiées avec un poids plus fort pour les récentes et les peu votées, exclusion des fiches de l'auteur.
- `Rankings` : requêtes SQL par thème, calculées à la demande avec cache court (5 minutes). Définitions publiées dans `docs/classement.md` : débattues = arguments + votes ; clivantes = équilibre oui/non parmi les votants (minimum 10 votes) ; nécessaires mais pas souhaitées = écart `necessary` − `desirable` ; soutien en hausse = part des révisions après arguments passant de non ou je ne sais pas à oui ; choisies dans les arbitrages = fréquence de sélection.
- `EmbeddingClient` : appel HTTP interne au service Python, hôte restreint, délai court, repli silencieux (pas de suggestion plutôt qu'une erreur pour l'utilisateur). `DuplicateFinder` : requête pgvector `<=>` sur les fiches publiées du même thème puis des autres thèmes, seuil et nombre configurables.
- `ConditionGrouper` : regroupement glouton par similarité cosinus au-dessus d'un seuil, libellé = condition la plus centrale du groupe.
- `TradeoffService` : validation de la combinaison (items appartiennent à l'exercice, contrainte atteinte selon `direction`), remplacement de la réponse, historique, résultats agrégés avec cache.
- Tous les seuils (similarité, poids du tirage, minimum de votes) dans `config/votalis.php`, surchargeables par l'environnement, valeurs par défaut documentées, valeurs de production hors dépôt.

## 5. Service Python d'embeddings

- `consensus/` : FastAPI, Python 3.12, `sentence-transformers`, modèle `intfloat/multilingual-e5-small` (384 dimensions, environ 500 Mo en mémoire, inférence CPU en quelques dizaines de millisecondes par texte). Voir Q1 pour la taille du modèle.
- Points d'entrée : `POST /embed` (liste de textes → vecteurs), `GET /health`. Aucun stockage, aucune journalisation des textes.
- Le modèle est téléchargé au build de l'image (pas au démarrage) depuis Hugging Face ; en production, l'image est reconstruite et poussée sur le registre de l'hébergeur, donc aucun appel sortant à l'exécution. Voir Q2 : Hugging Face est un hébergeur américain, le téléchargement n'a lieu qu'au build.
- Qualité : Ruff, mypy, pytest ; ajoutés au pipeline Woodpecker.
- Le même service accueillera le calcul de consensus en V2.

## 6. Interface

- Fiche : bloc de vote en deux questions, champ condition, puis résultats (pourcentages par question, conditions regroupées) une fois le vote posé ; bouton « réviser mon vote » après lecture des arguments.
- `/vote-rapide` : une fiche à la fois, format mobile d'abord, utilisable d'une main, arguments repliés, bouton « passer ».
- Page de thème : onglets de classement ; page « Comment fonctionne le classement » complétée.
- `/recherche` : résultats Meilisearch, filtres par thème.
- `/arbitrages`, `/arbitrages/{slug}` : exercice avec jauge, `/arbitrages/{slug}/resultats`, administration `/comite/arbitrages`.
- Formulaire de proposition : panneau « propositions proches » alimenté par la détection de doublons.

## 7. Tests

- Votes : clé unique, révision et conservation de l'initial, plafond côté serveur, interdiction sur sa propre fiche, verrou de la fiche, résultats masqués avant vote, vote rapide ne repropose jamais une fiche déjà votée.
- Classements : jeux de données synthétiques vérifiant chaque onglet ; aucun tri par soutien brut.
- Doublons : finder avec embeddings simulés (déterministes) ; test d'intégration marqué `@group embeddings` exécuté en CI contre le service réel pour le critère C2 ; repli silencieux si le service est indisponible.
- Conditions : regroupement et libellé.
- Arbitrages : contrainte non atteinte refusée, item étranger refusé, remplacement de réponse, historique, item sans chiffrage refusé, résultats agrégés.
- Python : tests pytest sur `/embed` (dimensions, normalisation, lot vide).
- Recherche : indexation et résultat sur une requête en français avec accents.

## 8. Ordre de réalisation

1. Phase A : migrations votes, `VoteService`, bloc de vote sur la fiche, vote rapide, résultats, classements, docs/classement.md. Point d'étape.
2. Phase B : service Python, image Docker, `EmbeddingClient`, colonne vecteur et file de calcul, détection de doublons, regroupement des conditions, Meilisearch et recherche. Point d'étape.
3. Phase C : arbitrages (modèle, administration, exercice, résultats). Recette C1 à C9, mise à jour de `docs/architecture.md` et de la CI, pull request.

## 9. Questions à valider

- **Q1 — Taille du modèle d'embeddings.** `multilingual-e5-small` (384 dimensions, ~500 Mo, recommandé au MVP) ou `multilingual-e5-base` (768 dimensions, ~1,1 Go, légèrement plus précis) ? Le choix fixe la dimension de la colonne vecteur ; changer plus tard demande un recalcul complet, prévu par `embedding_version`.
- **Q2 — Téléchargement du modèle.** Le modèle est téléchargé depuis Hugging Face (États-Unis) au moment de construire l'image Docker, jamais à l'exécution, et aucun texte n'y est envoyé. Acceptable, ou souhaitez-vous un miroir du modèle hébergé en Europe (dépôt Codeberg LFS ou stockage objet de l'hébergeur) dès le MVP ?
- **Q3 — Vote sur sa propre proposition.** Je recommande de l'interdire : l'auteur a déjà exprimé son soutien en déposant.
- **Q4 — « Après lecture des arguments ».** Je propose de considérer qu'un vote révisé depuis la fiche, où les arguments sont visibles, est « après lecture des arguments », et qu'un vote en mode rapide, arguments repliés, ne l'est pas, sauf si le participant les a dépliés. Convient-il ?
- **Q5 — Minimum de votes pour les classements.** Je propose de n'afficher une fiche dans « clivantes » et « nécessaires mais pas souhaitées » qu'à partir de 10 votes, pour éviter des classements fondés sur deux avis. Seuil configurable.
- **Q6 — Trois phases, une pull request.** Confirmez-vous ce découpage avec un point d'étape après chaque phase, ou préférez-vous trois pull requests successives pour relire des changements plus petits ?
