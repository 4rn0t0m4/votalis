# Plan du lot 7 — Classement par consensus

Statut : **codé le 9 octobre 2026, désactivé par défaut** (demande d'Arnaud : « je veux qu'il soit codé mais pas encore actif » ; recommandations Q1 à Q8 appliquées).
Référence : cahier des charges, sections 1 (faire émerger les mesures qui rassemblent des personnes d'opinions différentes), 2 (le consensus arrive en V2), 4.10 (onglets de classement), 5 (service de classement par consensus), 7.2 (anti-manipulation), 8 (pseudonymisation), 10 (table `consensus_scores`), 14. Branche : `lot-7-consensus`.

Demande d'origine : « tu as mis en place le vote par consensus ? », puis « oui » à la proposition d'écrire ce plan.

## 1. Objectif et cadre

Mettre en avant les propositions qui obtiennent un accord élevé **dans plusieurs familles de votants à la fois**, et non celles qui reçoivent simplement le plus de votes. C'est la promesse centrale du projet (section 1) et le seul onglet de classement encore inactif.

Le cahier des charges fixe déjà l'essentiel (section 5) : calcul horaire par le service Python, matrice participants × propositions, ACP à deux composantes, k-means avec k choisi entre 2 et 5 par score de silhouette, taux d'accord lissé par un estimateur bayésien, score = taux du groupe le moins favorable, calcul déterministe, activation seulement au-delà de 300 participants et 50 propositions, affichage par groupe sans jamais nommer les groupes. Ce plan précise la mise en œuvre et pose les questions que le cahier des charges ne tranche pas (section 7).

### Ce que le lot ne fera pas

| Hors lot | Raison |
| --- | --- |
| Familles et variantes de propositions (4.6), demandes de fusion | Fonction V2 distincte, sans lien technique avec le consensus |
| Ventilation des résultats d'arbitrage par famille de votants (4.9) | Dépend des familles calculées ici ; à faire dans un lot suivant une fois le calcul éprouvé |
| Vérification par téléphone | V2 distincte (sections 7.2 et 8) |
| Tri des arguments par consensus (4.4) | Proposé en question Q6 : recommandé pour un lot suivant |
| Nommer, décrire ou caractériser les groupes | Interdit par la section 5 : un groupe nommé devient une étiquette politique |

### Critères d'acceptation proposés

| # | Critère | Vérification |
| --- | --- | --- |
| C1 | Le calcul suit exactement l'algorithme publié dans `docs/classement.md` | Tests Python sur matrices construites à la main, résultat attendu calculé à la main |
| C2 | Calcul déterministe : mêmes votes, même graine, mêmes scores | Test : deux exécutions identiques ; test : permutation des lignes d'entrée sans effet sur les scores |
| C3 | Une campagne de 5 000 votes homogènes ne fait pas entrer une proposition dans le top 10 | Test Python sur population synthétique, plus test de bout en bout côté Laravel |
| C4 | Inactif sous les seuils (300 participants, 50 propositions, 7 votes par participant) : onglet avec message explicatif, aucun score affiché | Tests Laravel des deux côtés du seuil |
| C5 | Aucun identifiant de compte, e-mail ni pseudonyme ne sort de Laravel ; aucune appartenance individuelle à un groupe n'est conservée | Test du contenu envoyé au service ; schéma sans colonne de participant ; revue des journaux |
| C6 | Sur chaque fiche, le taux d'accord par groupe est affiché (« Groupe A : 82 % »), sans nom ni description de groupe, avec un lien vers l'explication | Tests de vue ; accessibilité (texte lisible sans couleur) |
| C7 | Un audit peut reproduire un calcul à partir d'un export pseudonymisé et de l'algorithme public | Commande d'export + script de recalcul documenté ; test : le recalcul redonne les scores enregistrés |
| C8 | Seuils, k minimal et maximal, lissage et graine lus dans la configuration privée ; valeurs par défaut de développement seulement dans le dépôt | `config/votalis.php` + `.env.example` ; aucune valeur de production commitée |
| C9 | Une panne du service Python n'affecte ni le vote ni l'affichage : les derniers scores restent servis, l'échec est journalisé | Test avec service indisponible |
| C10 | Règles et tests des lots 1 à 6 inchangés ; poids des pages et accessibilité conservés | Suite existante verte, `PageWeightTest`, `make a11y` |

## 2. Périmètre

### Phase A — Calcul dans le service Python (`consensus/`)

- Module pur `consensus/app/consensus.py` (aucune entrée/sortie) : `compute(matrix, proposal_ids, params) -> Result`.
  1. Ne garder que les participants ayant au moins `min_votes_per_participant` votes non nuls ou « je ne sais pas » exprimés (défaut 7).
  2. Matrice : oui = 1, non = −1, « je ne sais pas » ou absent = 0.
  3. ACP à deux composantes (centrée), puis k-means pour k de `k_min` à `k_max` (défaut 2 à 5), graine fixée, `n_init` fixé ; k retenu = meilleur score de silhouette.
  4. Groupes renumérotés par taille décroissante (A = le plus grand), pour que les libellés soient stables d'un calcul à l'autre tant que la structure ne change pas.
  5. Pour chaque proposition et chaque groupe : taux d'accord lissé = (oui + α) / (votants du groupe sur la fiche + 2α), α = 1 par défaut (a priori neutre à 50 %). Voir Q4 pour le traitement du « je ne sais pas ».
  6. Score de consensus = taux lissé du groupe le moins favorable ; clivage = écart entre le groupe le plus favorable et le moins favorable.
  7. Une proposition n'est notée que si chaque groupe compte au moins `min_voters_per_group` votants sur elle (défaut 5) ; sinon elle reçoit « données insuffisantes ».
- Point d'entrée interne `POST /consensus` : reçoit la matrice déjà pseudonymisée (voir Q2) et les paramètres, renvoie scores, clivages, détail par groupe, k, silhouette, nombre de participants retenus, version de l'algorithme. Rien n'est journalisé hormis les tailles.
- Dépendances ajoutées : `scikit-learn` (projet né à l'Inria, France, licence BSD) et `numpy` (déjà présent dans l'image via les dépendances existantes). Voir Q7.
- Tests (`consensus/tests/test_consensus.py`) : matrices manuelles (C1), déterminisme et invariance à l'ordre des lignes (C2), population synthétique à trois familles plus campagne homogène de 5 000 votes (C3), cas limites (une seule famille, matrice vide, propositions sans votes).

### Phase B — Intégration Laravel

- Commande `consensus:compute`, planifiée toutes les heures (`withoutOverlapping`) :
  1. vérifie les seuils (participants retenus, propositions publiées) ; sous les seuils, enregistre un calcul « inactif » et s'arrête ;
  2. construit la matrice à partir de `votes` (voir Q1 pour la question retenue), remplace chaque compte par un rang dans un ordre tiré au hasard à chaque calcul, n'envoie que ce rang et les identifiants publics des propositions ;
  3. appelle le service, valide la réponse, écrit le calcul et les scores dans une transaction ; en cas d'échec, conserve le calcul précédent (C9).
- Service `App\Services\Consensus` : dernier calcul actif, score et détail d'une proposition, statut lisible (« calculé le 9 octobre à 14 h, 412 participants, 3 groupes »).
- **Onglet « Les plus consensuelles »** dans `Rankings` : propositions notées, triées par score décroissant, métrique affichée « accord minimal 71 % dans 3 groupes » ; message actuel conservé tant que le calcul est inactif.
- **Onglet « Les plus clivantes »** : voir Q5.
- **Fiche** : bloc « Accord par groupe de votants », barres horizontales en SVG (même technique que les résultats actuels, compatible CSP), texte lisible sans couleur, phrase d'explication et lien vers « Comment fonctionne le classement ». Affiché après le vote, comme les autres résultats, pour ne pas influencer la réponse.
- **Page « Comment fonctionne le classement »** : ajout des caractéristiques du dernier calcul (date, version, nombre de participants retenus, k, silhouette) et des seuils d'activation.
- **Audit** : commande `consensus:export {calcul}` produisant la matrice pseudonymisée et les paramètres d'un calcul, et script `consensus/scripts/recalcul.py` qui redonne les mêmes scores (C7). L'export ne contient aucune donnée de compte.

### Phase C — Démonstration et documentation

- **Votants fictifs de développement** (voir Q8) : seeder `DemoVotersSeeder`, exécuté seulement hors production, qui crée des comptes `votant-fictif-NNN@example.test` répartis en trois profils d'opinion synthétiques et leur fait voter les fiches de démonstration avec du bruit, afin de franchir les seuils et de montrer le classement. Les profils sont tirés au hasard avec une graine fixe et ne correspondent à aucune sensibilité réelle.
- Documentation : `docs/classement.md` (paramètres par défaut, traitement du « je ne sais pas », règle de renumérotation, procédure d'audit), `docs/architecture.md` (flux Laravel ↔ service, tables), `docs/guide-developpement.md` (lancer un calcul en local), `.env.example` (nouvelles clés), README du service.

## 3. Modèle de données

| Table | Contenu |
| --- | --- |
| `consensus_runs` | nouvelle : `id`, `status` (`inactive`, `computed`, `failed`), `algo_version`, `participants`, `proposals`, `k`, `silhouette`, `params` (JSON : seuils, α, graine), `computed_at`, `error` (texte court, sans donnée personnelle) |
| `consensus_scores` | nouvelle, conforme à la section 10 du cahier des charges : `run_id`, `proposal_id`, `score` (nullable si données insuffisantes), `clivage`, `groups` (JSON : libellé, taux lissé, effectif de votants sur la fiche), `computed_at` ; clé primaire `(run_id, proposal_id)` |

Seuls les scores du dernier calcul réussi et des dix précédents sont conservés (purge à chaque calcul), pour permettre un audit récent sans accumuler d'historique. L'appartenance individuelle aux groupes n'est pas écrite (voir Q3).

## 4. Règles métier côté serveur

- Le score n'est jamais calculé ni modifié par Laravel : Laravel prépare la matrice, appelle le service et affiche.
- Aucun score n'est affiché si le dernier calcul est inactif ou date de plus de 24 heures (seuil configurable) : l'onglet revient alors au message explicatif plutôt que de montrer des données périmées.
- Les votes des comptes supprimés disparaissent avec eux (cascade existante) ; ils sortent donc du calcul suivant.
- Les comptes privilégiés (modération, comité, administration) votent comme les autres participants : pas de traitement particulier.
- Les participants ayant moins de 7 votes ne pèsent pas dans la formation des groupes ; leurs votes ne comptent pas non plus dans les taux par groupe.

## 5. Tests

- Python : C1, C2, C3 et cas limites (phase A).
- Laravel : seuils et statut inactif (C4) ; contenu exact envoyé au service, sans identifiant de compte (C5) ; affichage par groupe sur la fiche et dans l'onglet (C6) ; export puis recalcul (C7, test d'intégration marqué, exécuté en CI avec le service de test) ; service indisponible ou réponse invalide (C9) ; scores périmés masqués.
- Bout en bout : population synthétique plus campagne coordonnée injectées en base, `consensus:compute`, vérification que la fiche ciblée n'apparaît pas dans les dix premières de l'onglet (C3).
- Non-régression : suite complète, `PageWeightTest`, `make a11y` avec une fiche notée.

## 6. Ordre de réalisation

1. Phase A complète, tests Python verts.
2. Migrations, commande, service Laravel, tests de seuil et de pseudonymisation.
3. Onglets et fiche, page d'explication, accessibilité.
4. Export et recalcul d'audit.
5. Votants fictifs, captures, documentation, recette.

## 7. Questions posées à la validation

| # | Question | Recommandation |
| --- | --- | --- |
| Q1 | Le cahier des charges prévoit un seul vote (−1, 0, 1) ; la plateforme en recueille deux (« souhaitable », « nécessaire »). Sur quelle question calculer le consensus ? | **« Souhaitable »** pour former les groupes et noter, comme l'onglet « clivantes » actuel ; le taux « nécessaire » par groupe est affiché en complément sur la fiche, sans entrer dans le score |
| Q2 | Le service Python lit-il la base lui-même, ou Laravel lui envoie-t-il la matrice ? | **Laravel envoie la matrice pseudonymisée** au point d'entrée interne : le service n'a ni accès à la base ni identifiant de compte, il reste sans état et testable. Le cahier des charges autorise les deux (« via la base et une API interne ») |
| Q3 | Conserver l'appartenance de chaque participant à un groupe (prévue « pseudonymisée » par la section 5) ? | **Non** : rien ne l'utilise dans ce lot, et ne pas la conserver est plus sûr (minimisation, section 8). L'export d'audit permet de la recalculer. À réintroduire si la ventilation des arbitrages par famille est faite plus tard |
| Q4 | Un « je ne sais pas » compte-t-il dans le dénominateur du taux d'accord ? | **Oui** : il n'est pas un accord, et l'exclure gonflerait le score des fiches mal comprises. Il vaut 0 dans la matrice, comme le prévoit la section 5 |
| Q5 | Quand le calcul est actif, l'onglet « Les plus clivantes » doit-il passer à l'écart entre groupes ? | **Oui**, c'est ce que prévoit la section 5 ; sous les seuils, il garde la formule actuelle (écart entre oui et non sur l'ensemble) |
| Q6 | Trier aussi les arguments par consensus (4.4) dans ce lot ? | **Non, lot suivant** : il faut d'abord observer le comportement des groupes sur les votes réels |
| Q7 | Accepter `scikit-learn` (Inria, France) et `numpy` comme dépendances du service ? | **Oui** : bibliothèques libres, exécutées localement, sans service tiers ; `numpy` est déjà présent dans l'image |
| Q8 | Créer des votants fictifs en développement pour montrer le classement avant d'avoir 300 participants réels ? | **Oui, 400 comptes fictifs**, uniquement hors production, clairement nommés, recréés à chaque `migrate:fresh --seed` |

Préalable : fusionner la pull request du lot 6 (#4) avant d'ouvrir celle du lot 7, pour que sa différence ne contienne que ce lot.

## 8. Point d'étape et recette (9 octobre 2026)

Réalisé selon les recommandations Q1 à Q8, derrière l'interrupteur `CONSENSUS_ENABLED` (faux par défaut) : éteint, aucun calcul ni affichage, même si des scores existent en base.

| # | Critère | Résultat |
| --- | --- | --- |
| C1 | Algorithme publié | `consensus/app/consensus.py` (`consensus-min-1`) ; test « cas calculé à la main » sur deux camps de 6 et 4 votants |
| C2 | Déterminisme | Tests : deux exécutions identiques ; rangs et ordre d'envoi permutés sans effet |
| C3 | Campagne de 5 000 votes | Test Python : 500 comptes coordonnés, 10 votes identiques chacun, sur une population synthétique de 600 votants ; la cible n'entre pas dans le top 10. Règle ajoutée pour y parvenir : un groupe trop peu présent sur une fiche est écarté pour cette fiche au lieu de bloquer sa notation (sinon le groupe formé par la campagne, qui ne vote pas sur les fiches consensuelles, les rendait toutes « non notées ») |
| C4 | Seuils | `ConsensusTest` : sous les seuils, calcul « inactif » sans appel, message explicatif |
| C5 | Pseudonymisation | Test sur la charge envoyée : rangs 0..n-1, aucun pseudonyme ni e-mail ; schéma sans appartenance individuelle |
| C6 | Affichage par groupe | Bloc « Accord par groupe de votants » après le vote, barres SVG, texte sans couleur ; test de vue |
| C7 | Audit | `--export` + `scripts/recalcul.py` ; vérifié sur les données de développement : même empreinte `bcefb09a…`, mêmes scores |
| C8 | Configuration | `config/votalis.php` + `.env.example`, valeurs par défaut publiées ; rien de production |
| C9 | Panne | Tests : connexion refusée et réponse invalide → calcul « failed », scores précédents servis ; scores de plus de 24 h masqués |
| C10 | Non-régression | 247 tests PHP, 13 tests Python, Pint, PHPStan niveau 8, ruff, mypy |

Essai de bout en bout en développement : 400 votants fictifs (11 157 votes), calcul forcé contre le service reconstruit : 3 groupes de 136, 133 et 131 participants, silhouette 0,60, les dix fiches « consensuelles » du jeu fictif en tête. Captures dans `docs/plans/captures/lot-7/`.

Écart par rapport au plan : la table `consensus_runs` porte aussi l'empreinte SHA-256 de la charge envoyée, pour qu'un audit puisse prouver que l'export correspond au calcul enregistré.
