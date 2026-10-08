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
| Les plus clivantes | 1 − \|oui − non\| / (oui + non) sur la question « souhaitable », décroissant ; à égalité, le plus de votes | au moins 10 votes |
| Nécessaires mais pas souhaitées | (oui « nécessaire » − oui « souhaitable ») / total, décroissant, seulement si positif | au moins 10 votes |
| Soutien en hausse après les arguments | Parmi les votes révisés après lecture des arguments : part passée de « non » ou « je ne sais pas » à « oui » sur la question « souhaitable », décroissante | au moins 1 révision |
| Les plus choisies dans les arbitrages | Part des réponses aux arbitrages ouverts ou clos qui retiennent la mesure (choix / réponses à l'exercice), décroissante ; à égalité, le plus de choix | au moins 1 choix |
| Les plus consensuelles | V2, voir ci-dessous | à venir |

## Vote rapide

Le vote rapide propose une fiche à la fois, non encore votée par le participant et jamais l'une des siennes. Le tirage est aléatoire et pondéré : poids × 3 pour une fiche publiée depuis moins de 14 jours, × 2 pour une fiche de moins de 10 votes. Les fiches passées pendant la session ne sont pas reproposées tant qu'il en reste d'autres.

## V2 : consensus par familles de votants

Principe : une proposition est mise en avant si elle obtient un taux d'accord élevé dans plusieurs familles de votants à la fois, et non parce qu'elle reçoit beaucoup de votes. Traitement inspiré de Pol.is (AGPL), exécuté toutes les heures par le service Python :

1. Lire la matrice participants × propositions (accord = 1, désaccord = −1, « je ne sais pas » ou absent = 0), en ne gardant que les participants ayant voté au moins 7 fois.
2. Réduire les dimensions (ACP, 2 composantes) puis regrouper les participants (k-means, k entre 2 et 5 choisi par score de silhouette).
3. Pour chaque proposition et chaque groupe, calculer un taux d'accord lissé (estimateur bayésien, a priori neutre).
4. Score de consensus = taux d'accord lissé du groupe le moins favorable (approche « minimum », remplaçable).
5. Identifier les propositions clivantes (écart maximal entre groupes).
6. Écrire scores, appartenances pseudonymisées, date de calcul et version de l'algorithme.

Exigences : calcul déterministe (graine fixée), activation seulement au-delà de seuils de participants et de propositions, tests sur jeux synthétiques (une campagne de 5 000 votes homogènes ne fait pas entrer une proposition dans le top 10). Sur chaque fiche, le taux d'accord par groupe est affiché sans jamais nommer les groupes.
