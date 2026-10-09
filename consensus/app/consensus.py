"""Classement par consensus (cahier des charges, section 5 ; docs/classement.md).

Module pur : aucune entrée/sortie, aucun identifiant de compte. Les participants arrivent sous
forme de rangs anonymes, les propositions sous forme d'identifiants publics.

Étapes :
1. ne garder que les participants ayant exprimé au moins `min_votes_per_participant` votes ;
2. matrice « souhaitable » : oui = 1, non = -1, « je ne sais pas » ou absent = 0 ;
3. ordre canonique des lignes (le résultat ne dépend pas de l'ordre d'envoi) ;
4. ACP à deux composantes, puis k-means pour k de `k_min` à `k_max`, graine fixée ;
   k retenu = meilleur score de silhouette (le plus petit k en cas d'égalité) ;
5. groupes renumérotés par taille décroissante (A = le plus grand) ;
6. taux d'accord lissé par groupe et par proposition : (oui + alpha) / (votants + 2 alpha),
   « je ne sais pas » compris dans les votants ;
7. score = taux du groupe le moins favorable ; clivage = écart entre le plus et le moins favorable.
   Un groupe comptant moins de `min_voters_per_group` votants sur une proposition est écarté pour
   cette proposition (affiché « trop peu de votants ») ; il faut au moins deux groupes représentés.
   Un groupe ne peut donc pas empêcher la notation d'une proposition en s'abstenant : c'est ce
   qu'exploiterait une campagne coordonnée qui ne vote que sur ses propres cibles.
"""

from __future__ import annotations

import string
from dataclasses import dataclass, field

import numpy as np
from numpy.typing import NDArray
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import silhouette_score

ALGO_VERSION = "consensus-min-1"


@dataclass(frozen=True)
class Params:
    min_votes_per_participant: int = 7
    k_min: int = 2
    k_max: int = 5
    alpha: float = 1.0
    min_voters_per_group: int = 5
    seed: int = 20261009
    n_init: int = 10


@dataclass(frozen=True)
class Vote:
    """Un vote : rang anonyme du participant, proposition (identifiant public), deux réponses."""

    participant: int
    proposal: int
    desirable: int
    necessary: int


@dataclass
class GroupRate:
    label: str
    voters: int
    agree_rate: float
    necessary_rate: float
    represented: bool


@dataclass
class ProposalScore:
    proposal: int
    score: float | None
    divisiveness: float | None
    groups: list[GroupRate] = field(default_factory=list)


@dataclass
class Group:
    label: str
    size: int


@dataclass
class Result:
    algo_version: str
    status: str  # "computed" ou "insufficient"
    participants: int
    k: int
    silhouette: float | None
    groups: list[Group]
    proposals: list[ProposalScore]


def _labels(k: int) -> list[str]:
    return list(string.ascii_uppercase[:k])


def _empty(status: str, participants: int, proposals: list[int]) -> Result:
    return Result(
        algo_version=ALGO_VERSION,
        status=status,
        participants=participants,
        k=0,
        silhouette=None,
        groups=[],
        proposals=[ProposalScore(proposal=p, score=None, divisiveness=None) for p in proposals],
    )


def compute(votes: list[Vote], proposals: list[int], params: Params) -> Result:
    for vote in votes:
        if vote.desirable not in (-1, 0, 1) or vote.necessary not in (-1, 0, 1):
            raise ValueError("Une réponse vaut -1, 0 ou 1.")

    columns = {proposal: index for index, proposal in enumerate(proposals)}
    by_participant: dict[int, dict[int, tuple[int, int]]] = {}
    for vote in votes:
        if vote.proposal not in columns:
            continue
        by_participant.setdefault(vote.participant, {})[columns[vote.proposal]] = (
            vote.desirable,
            vote.necessary,
        )

    kept = [
        answers
        for answers in by_participant.values()
        if len(answers) >= params.min_votes_per_participant
    ]
    n, m = len(kept), len(proposals)

    if m == 0 or n <= params.k_min:
        return _empty("insufficient", n, proposals)

    # present : le participant s'est exprimé (oui, non ou « je ne sais pas ») sur la proposition.
    desirable = np.zeros((n, m), dtype=np.float64)
    necessary = np.zeros((n, m), dtype=np.float64)
    present = np.zeros((n, m), dtype=bool)
    for row, answers in enumerate(kept):
        for column, (d, nec) in answers.items():
            desirable[row, column] = d
            necessary[row, column] = nec
            present[row, column] = True

    # Ordre canonique : tri lexicographique sur (souhaitable, nécessaire, présence).
    keys = np.hstack([desirable, necessary, present.astype(np.float64)])
    order = np.lexsort(keys.T[::-1])
    desirable, necessary, present = desirable[order], necessary[order], present[order]

    # Moins de deux profils de vote distincts : aucune famille à distinguer.
    distinct = len(np.unique(desirable, axis=0))
    if distinct < 2:
        return _empty("insufficient", n, proposals)

    components = min(2, m, n)
    reduced = PCA(n_components=components, svd_solver="full").fit_transform(desirable)

    best: tuple[float, int, NDArray[np.int64]] | None = None
    for k in range(params.k_min, min(params.k_max, n - 1, distinct) + 1):
        labels = KMeans(n_clusters=k, n_init=params.n_init, random_state=params.seed).fit_predict(
            reduced
        )
        if len(set(labels.tolist())) < 2:
            continue
        score = float(silhouette_score(reduced, labels))
        if best is None or score > best[0] + 1e-12:
            best = (score, k, labels.astype(np.int64))

    if best is None:
        return _empty("insufficient", n, proposals)

    silhouette, k, raw = best
    # Renumérotation par taille décroissante ; à égalité, premier apparu dans l'ordre canonique.
    sizes = {int(c): int((raw == c).sum()) for c in set(raw.tolist())}
    first = {int(c): int(np.argmax(raw == c)) for c in sizes}
    ranked = sorted(sizes, key=lambda c: (-sizes[c], first[c]))
    names = _labels(len(ranked))
    label_of = {cluster: names[i] for i, cluster in enumerate(ranked)}
    members = {label_of[c]: raw == c for c in ranked}

    alpha = params.alpha
    scores: list[ProposalScore] = []
    for column, proposal in enumerate(proposals):
        rates: list[GroupRate] = []
        for label in names:
            mask = members[label] & present[:, column]
            voters = int(mask.sum())
            agree = int((desirable[mask, column] == 1).sum())
            need = int((necessary[mask, column] == 1).sum())
            rates.append(
                GroupRate(
                    label=label,
                    voters=voters,
                    agree_rate=round((agree + alpha) / (voters + 2 * alpha), 4),
                    necessary_rate=round((need + alpha) / (voters + 2 * alpha), 4),
                    represented=voters >= params.min_voters_per_group,
                )
            )

        values = [r.agree_rate for r in rates if r.represented]
        if len(values) >= 2:
            scores.append(
                ProposalScore(
                    proposal=proposal,
                    score=round(min(values), 4),
                    divisiveness=round(max(values) - min(values), 4),
                    groups=rates,
                )
            )
        else:
            scores.append(
                ProposalScore(proposal=proposal, score=None, divisiveness=None, groups=rates)
            )

    return Result(
        algo_version=ALGO_VERSION,
        status="computed",
        participants=n,
        k=k,
        silhouette=round(silhouette, 4),
        groups=[Group(label=label_of[c], size=sizes[c]) for c in ranked],
        proposals=scores,
    )
