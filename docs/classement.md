# Comment fonctionne le classement

Ce document décrit les classements, publics et reproductibles. Les seuils numériques ont une valeur par défaut documentée ici et peuvent être ajustés par la configuration privée.

## Principe : aucun classement unique par soutien

Aucune liste ne trie les propositions par nombre de « oui ». Chaque thème propose plusieurs onglets, calculés par `App\Services\Rankings` et mis en cache cinq minutes. Chaque proposition affiche la métrique qui justifie sa place.

## Le double vote

Chaque participant répond à deux questions par proposition : « Souhaitable pour vous ? » et « Nécessaire pour le pays, même si elle vous coûte ? », avec trois réponses (oui = 1, je ne sais pas = 0, non = −1). Un « oui » peut être assorti d'une condition de 200 caractères. Le vote est révisable ; le vote initial est conservé. Une révision faite depuis une vue où les arguments sont visibles (la fiche, ou le vote rapide après dépliage des arguments) est marquée « après lecture des arguments ». Le premier vote reçu verrouille le fond de la fiche.

## Onglets du MVP

| Onglet | Définition | Seuil |
| --- | --- | --- |
| Récentes | Ordre de publication | aucun |
| Les plus débattues | Somme des votes et des arguments publiés, décroissante | aucun |
| Les plus clivantes | 1 − \|oui − non\| / (oui + non) sur la question « souhaitable », décroissant ; à égalité, le plus de votes. Quand un calcul de consensus est actif : écart d'accord entre le groupe le plus et le moins favorable, décroissant | au moins 10 votes |
| Nécessaires mais pas souhaitées | (oui « nécessaire » − oui « souhaitable ») / total, décroissant, seulement si positif | au moins 10 votes |
| Soutien en hausse après les arguments | Parmi les votes révisés après lecture des arguments : part passée de « non » ou « je ne sais pas » à « oui » sur la question « souhaitable », décroissante | au moins 1 révision |
| Les plus choisies dans les arbitrages | Part des réponses aux arbitrages ouverts ou clos qui retiennent la mesure (choix / réponses à l'exercice), décroissante ; à égalité, le plus de choix, puis la fiche la plus ancienne | au moins 1 choix |
| Les plus consensuelles | Taux d'accord lissé du groupe de votants le moins favorable, décroissant (voir ci-dessous) | calcul activé et à jour |

## Vote rapide

Le vote rapide propose une fiche à la fois, non encore votée par le participant et jamais l'une des siennes. Le tirage est aléatoire et pondéré : poids × 3 pour une fiche publiée depuis moins de 14 jours, × 2 pour une fiche de moins de 10 votes. Les fiches passées pendant la session ne sont pas reproposées tant qu'il en reste d'autres.

## Les plus consensuelles : familles de votants (lot 7)

Principe : une proposition est mise en avant si elle obtient un taux d'accord élevé dans plusieurs familles de votants à la fois, et non parce qu'elle reçoit beaucoup de votes. Traitement inspiré de Pol.is (AGPL), codé dans le service Python (`consensus/app/consensus.py`, version `consensus-min-1`) et appelé toutes les heures par `consensus:compute`. **Désactivé par défaut** (`CONSENSUS_ENABLED=false`) : tant qu'il l'est, l'onglet affiche une explication et rien n'est calculé.

1. Laravel construit la matrice des votes sur les propositions publiées, en ne gardant que les participants ayant voté au moins 7 fois (`CONSENSUS_MIN_VOTES_PER_PARTICIPANT`). Chaque compte est remplacé par un rang tiré au hasard à chaque calcul ; seuls ces rangs, les identifiants publics des propositions et les deux réponses (souhaitable, nécessaire) sont envoyés au service. L'empreinte SHA-256 de la charge est conservée.
2. Le calcul ne démarre qu'avec au moins 300 participants retenus et 50 propositions publiées (`CONSENSUS_MIN_PARTICIPANTS`, `CONSENSUS_MIN_PROPOSALS`) ; sinon le calcul est enregistré « inactif » sans appel au service.
3. Matrice « souhaitable » : oui = 1, non = −1, « je ne sais pas » ou absent = 0. Les lignes sont mises dans un ordre canonique : le résultat ne dépend ni de l'ordre d'envoi ni des rangs.
4. ACP à deux composantes, puis k-means pour k de 2 à 5 (`CONSENSUS_K_MIN`, `CONSENSUS_K_MAX`) avec une graine fixée (`CONSENSUS_SEED`) ; k retenu = meilleur score de silhouette. Les groupes sont renumérotés par taille décroissante (A = le plus grand). Moins de deux profils de vote distincts : calcul « insuffisant ».
5. Pour chaque proposition et chaque groupe : taux d'accord lissé = (oui + α) / (votants + 2α), α = 1 (`CONSENSUS_ALPHA`), a priori neutre à 50 %. « Je ne sais pas » compte parmi les votants, pas comme un accord. Le même taux est calculé pour « nécessaire », à titre d'information.
6. Un groupe comptant moins de 5 votants sur une proposition (`CONSENSUS_MIN_VOTERS_PER_GROUP`) est écarté pour cette proposition ; il faut au moins deux groupes représentés pour la noter. Un groupe ne peut donc pas empêcher la notation d'une mesure en s'abstenant, ce qu'exploiterait une campagne qui ne vote que sur ses cibles.
7. Score = taux lissé du groupe représenté le moins favorable ; clivage = écart entre le plus et le moins favorable.
8. Laravel enregistre le calcul (`consensus_runs` : effectifs, k, silhouette, paramètres, empreinte, version) et les scores (`consensus_scores`). L'appartenance individuelle aux groupes n'est jamais écrite. Seuls les scores des dix derniers calculs réussis sont conservés.

Affichage : l'onglet « Les plus consensuelles » trie par score décroissant (« accord d'au moins 71 % dans chacun des 3 groupes ») ; quand un calcul est actif, « Les plus clivantes » trie par écart entre groupes au lieu de l'écart oui/non. Sur chaque fiche, après le vote, le taux d'accord de chaque groupe est affiché (« Groupe A : 82 % »), sans nom ni description de groupe. Les scores de plus de 24 heures (`CONSENSUS_MAX_AGE_HOURS`) ne sont plus affichés ; une panne du service laisse le calcul précédent en place.

Audit : `php artisan consensus:compute --export=fichier.json` écrit la charge exacte envoyée ; `python consensus/scripts/recalcul.py fichier.json --sha256 <empreinte>` la rejoue et redonne les mêmes scores. Tests : cas calculés à la main, déterminisme, invariance à l'ordre, et une campagne de 5 000 votes homogènes qui ne fait entrer aucune fiche dans le top 10 (`consensus/tests/test_consensus.py`).
