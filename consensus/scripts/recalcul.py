"""Recalcul d'audit : rejoue un calcul de consensus à partir de la charge exportée par Laravel.

Usage : python scripts/recalcul.py export.json [--sha256 EMPREINTE]

La charge est celle écrite par `php artisan consensus:compute --export=export.json` :
propositions, votes pseudonymisés (rangs, pas de comptes) et paramètres. Le script affiche
l'empreinte SHA-256 du fichier (à comparer avec `consensus_runs.input_digest`), puis les scores,
du plus consensuel au moins consensuel, pour comparaison avec `consensus_scores`.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from app.consensus import Params, Vote, compute  # noqa: E402


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("export", type=Path)
    parser.add_argument("--sha256", help="empreinte attendue (consensus_runs.input_digest)")
    args = parser.parse_args()

    raw = args.export.read_bytes()
    digest = hashlib.sha256(raw).hexdigest()
    print(f"empreinte sha256 : {digest}")
    if args.sha256 and args.sha256.lower() != digest:
        print("ATTENTION : l'empreinte ne correspond pas au calcul enregistré.")
        return 2

    payload = json.loads(raw)
    params = Params(**payload.get("params", {}))
    votes = [Vote(*vote) for vote in payload["votes"]]
    result = compute(votes, payload["proposals"], params)

    print(f"version : {result.algo_version} ; statut : {result.status}")
    print(f"participants retenus : {result.participants} ; groupes : {result.k}")
    print(f"silhouette : {result.silhouette}")
    for group in result.groups:
        print(f"  groupe {group.label} : {group.size} participants")
    scored = sorted(
        (p for p in result.proposals if p.score is not None),
        key=lambda p: (-(p.score or 0), p.proposal),
    )
    print("proposition ; score ; clivage ; accord par groupe")
    for p in scored:
        detail = ", ".join(
            f"{g.label} {g.agree_rate:.4f} ({g.voters})" for g in p.groups if g.represented
        )
        print(f"{p.proposal} ; {p.score:.4f} ; {p.divisiveness:.4f} ; {detail}")
    unscored = [p.proposal for p in result.proposals if p.score is None]
    if unscored:
        print(f"non notées (données insuffisantes) : {len(unscored)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
