# Comment fonctionne le classement

Ce document décrit l'algorithme de classement, public et reproductible. Les seuils numériques vivent dans une configuration privée.

## MVP : pas de classement par consensus

Tant que la plateforme compte moins de participants et de propositions que les seuils requis, aucun score de consensus n'est calculé :

- les listes de propositions sont triées par date ;
- le vote rapide présente les propositions dans un ordre partiellement aléatoire, pour donner leur chance aux propositions récentes ;
- les onglets « les plus débattues », « les plus clivantes », « nécessaires mais pas souhaitées », « soutien en hausse après lecture des arguments » et « les plus choisies dans les arbitrages » reposent sur des comptages simples décrits au lot 3.

Aucun classement unique par nombre de soutiens n'existe, à aucun moment.

## V2 : consensus par familles de votants

Principe : une proposition est mise en avant si elle obtient un taux d'accord élevé dans plusieurs familles de votants à la fois, et non parce qu'elle reçoit beaucoup de votes. Traitement inspiré de Pol.is (AGPL), exécuté toutes les heures par le service Python :

1. Lire la matrice participants × propositions (accord = 1, désaccord = −1, « je ne sais pas » ou absent = 0), en ne gardant que les participants ayant voté au moins 7 fois.
2. Réduire les dimensions (ACP, 2 composantes) puis regrouper les participants (k-means, k entre 2 et 5 choisi par score de silhouette).
3. Pour chaque proposition et chaque groupe, calculer un taux d'accord lissé (estimateur bayésien, a priori neutre).
4. Score de consensus = taux d'accord lissé du groupe le moins favorable (approche « minimum », remplaçable).
5. Identifier les propositions clivantes (écart maximal entre groupes).
6. Écrire scores, appartenances pseudonymisées, date de calcul et version de l'algorithme.

Exigences : calcul déterministe (graine fixée), activation seulement au-delà de seuils de participants et de propositions, tests sur jeux synthétiques (une campagne de 5 000 votes homogènes ne fait pas entrer une proposition dans le top 10). Sur chaque fiche, le taux d'accord par groupe est affiché sans jamais nommer les groupes.
