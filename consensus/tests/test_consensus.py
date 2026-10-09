"""Classement par consensus : cas calculés à la main, déterminisme, résistance aux campagnes."""

from __future__ import annotations

import random

import pytest
from fastapi.testclient import TestClient

from app.consensus import ALGO_VERSION, Params, ProposalScore, Vote, compute
from app.main import create_app
from tests.test_api import FakeEmbedder

PARAMS = Params(min_votes_per_participant=3, min_voters_per_group=2)


def two_camps() -> tuple[list[Vote], list[int]]:
    """Deux camps nets de 6 et 4 personnes sur 4 propositions.

    - 101 : tout le monde dit oui (consensuelle) ;
    - 102 : le camp de 6 dit oui, le camp de 4 dit non (clivante) ;
    - 103 : le camp de 6 dit non, le camp de 4 dit oui (clivante) ;
    - 104 : tout le monde dit « je ne sais pas ».
    """
    votes: list[Vote] = []
    for person in range(10):
        big = person < 6
        votes += [
            Vote(person, 101, 1, 1),
            Vote(person, 102, 1 if big else -1, 0),
            Vote(person, 103, -1 if big else 1, 0),
            Vote(person, 104, 0, 0),
        ]
    return votes, [101, 102, 103, 104]


def by_id(result_proposals: list[ProposalScore], proposal: int) -> ProposalScore:
    return next(p for p in result_proposals if p.proposal == proposal)


def test_cas_calcule_a_la_main() -> None:
    votes, proposals = two_camps()
    result = compute(votes, proposals, PARAMS)

    assert result.status == "computed"
    assert result.algo_version == ALGO_VERSION
    assert result.k == 2
    assert [(g.label, g.size) for g in result.groups] == [("A", 6), ("B", 4)]

    # 101 : groupe A (6 oui) = 7/8, groupe B (4 oui) = 5/6 ; score = min.
    consensual = by_id(result.proposals, 101)
    assert [g.agree_rate for g in consensual.groups] == [0.875, 0.8333]
    assert consensual.score == 0.8333
    assert consensual.divisiveness == round(0.875 - 0.8333, 4)

    # 102 : A = 7/8, B = 1/6 ; clivage maximal parmi les quatre.
    divisive = by_id(result.proposals, 102)
    assert divisive.score == 0.1667
    assert divisive.divisiveness == round(0.875 - 0.1667, 4)

    # 104 : « je ne sais pas » compte dans les votants mais pas dans l'accord : A = 1/8, B = 1/6.
    unknown = by_id(result.proposals, 104)
    assert [g.voters for g in unknown.groups] == [6, 4]
    assert unknown.score == 0.125

    # Le taux « nécessaire » est calculé à part (101 : tous oui).
    assert [g.necessary_rate for g in consensual.groups] == [0.875, 0.8333]

    ranking = sorted(
        (p for p in result.proposals if p.score is not None), key=lambda p: -(p.score or 0)
    )
    assert ranking[0].proposal == 101


def test_resultat_identique_d_une_execution_a_l_autre_et_quel_que_soit_l_ordre() -> None:
    votes, proposals = synthetic_population(seed=3)
    first = compute(votes, proposals, Params())
    second = compute(votes, proposals, Params())
    assert first == second

    # Rangs anonymes et ordre d'envoi différents : mêmes groupes, mêmes scores.
    shuffled = votes[:]
    random.Random(42).shuffle(shuffled)  # noqa: S311 - données de test
    ranks = random.Random(7).sample(range(10_000), 10_000)  # noqa: S311
    renumber = {p: i for i, p in enumerate(ranks)}
    renamed = [
        Vote(renumber[v.participant], v.proposal, v.desirable, v.necessary) for v in shuffled
    ]
    third = compute(renamed, proposals, Params())
    assert third == first


def test_participants_sous_le_seuil_ecartes_et_groupes_trop_petits() -> None:
    votes, proposals = two_camps()
    # Deux participants n'ayant voté qu'une fois : écartés.
    votes += [Vote(50, 101, 1, 1), Vote(51, 101, -1, -1)]
    result = compute(votes, proposals, PARAMS)
    assert result.participants == 10

    strict = compute(votes, proposals, Params(min_votes_per_participant=3, min_voters_per_group=5))
    # Le groupe B ne compte que 4 votants : il est écarté, et un seul groupe ne suffit pas à noter.
    assert all(p.score is None for p in strict.proposals)
    assert by_id(strict.proposals, 101).groups[1].voters == 4
    assert by_id(strict.proposals, 101).groups[1].represented is False


def test_donnees_insuffisantes() -> None:
    assert compute([], [1, 2], Params()).status == "insufficient"
    assert compute([Vote(1, 1, 1, 1)], [], Params()).status == "insufficient"
    # Tout le monde vote pareil : une seule famille, aucun regroupement possible.
    identical = [Vote(p, j, 1, 1) for p in range(30) for j in range(10)]
    result = compute(identical, list(range(10)), Params())
    assert result.status == "insufficient"
    assert all(p.score is None for p in result.proposals)


def test_reponse_invalide_refusee() -> None:
    with pytest.raises(ValueError):
        compute([Vote(1, 1, 2, 0)], [1], Params())


def synthetic_population(
    seed: int, families: int = 3, per_family: int = 200, proposals: int = 60
) -> tuple[list[Vote], list[int]]:
    """Population synthétique : chaque famille a sa probabilité d'accord par proposition.

    Les dix premières propositions sont consensuelles (accord fort partout) ; les autres plaisent
    à une famille et déplaisent aux autres. Chaque participant vote sur 20 à 40 propositions.
    """
    rng = random.Random(seed)  # noqa: S311 - population synthétique
    ids = list(range(1000, 1000 + proposals))
    liking: list[list[float]] = []
    for family in range(families):
        row = []
        for index in range(proposals):
            if index < 10:
                row.append(0.85)
            else:
                row.append(0.85 if index % families == family else 0.15)
        liking.append(row)

    votes: list[Vote] = []
    participant = 0
    for family in range(families):
        for _ in range(per_family):
            for index in rng.sample(range(proposals), rng.randint(20, 40)):
                draw = rng.random()
                p = liking[family][index]
                answer = 1 if draw < p else (-1 if draw < 0.95 else 0)
                votes.append(Vote(participant, ids[index], answer, answer))
            participant += 1
    return votes, ids


def top(result_proposals: list[ProposalScore], n: int) -> list[int]:
    scored = [p for p in result_proposals if p.score is not None]
    scored.sort(key=lambda p: (-(p.score or 0), p.proposal))
    return [p.proposal for p in scored[:n]]


def test_les_mesures_consensuelles_arrivent_en_tete() -> None:
    votes, ids = synthetic_population(seed=1)
    result = compute(votes, ids, Params())

    assert result.status == "computed"
    assert result.k == 3
    assert set(top(result.proposals, 10)) == set(ids[:10])


def test_une_campagne_de_5000_votes_homogenes_ne_fait_pas_entrer_une_fiche_dans_le_top_10() -> None:
    votes, ids = synthetic_population(seed=2)
    target = ids[11]  # plaît à une seule famille
    others = [i for i in ids[10:] if i != target][:9]

    # 500 comptes coordonnés, chacun 10 votes identiques : oui sur la cible et sur 9 autres fiches.
    campaign = [
        Vote(100_000 + account, proposal, 1, 1)
        for account in range(500)
        for proposal in [target, *others]
    ]
    assert len(campaign) == 5000

    result = compute(votes + campaign, ids, Params())
    assert result.status == "computed"
    assert target not in top(result.proposals, 10)
    # Le groupe formé par la campagne ne vote pas sur les fiches consensuelles : il est écarté pour
    # elles au lieu de les rendre impossibles à noter.
    assert set(top(result.proposals, 10)) == set(ids[:10])


def test_point_d_entree_consensus() -> None:
    votes, proposals = two_camps()
    payload = {
        "proposals": proposals,
        "votes": [[v.participant, v.proposal, v.desirable, v.necessary] for v in votes],
        "params": {"min_votes_per_participant": 3, "min_voters_per_group": 2},
    }
    with TestClient(create_app(FakeEmbedder())) as c:
        response = c.post("/consensus", json=payload)
        bad = c.post("/consensus", json={**payload, "params": {"k_min": 4, "k_max": 2}})
        invalid = c.post("/consensus", json={**payload, "votes": [[1, 101, 3, 0]]})

    assert response.status_code == 200
    body = response.json()
    assert body["status"] == "computed"
    assert body["k"] == 2
    assert body["groups"] == [{"label": "A", "size": 6}, {"label": "B", "size": 4}]
    assert next(p for p in body["proposals"] if p["proposal"] == 101)["score"] == 0.8333
    assert bad.status_code == 422
    assert invalid.status_code == 422
