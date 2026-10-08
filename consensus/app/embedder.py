"""Calcul d'embeddings avec un modèle multilingue auto-hébergé (famille multilingual-e5).

Les textes ne quittent jamais le réseau privé : le modèle est chargé depuis le cache local
constitué au build de l'image (TRANSFORMERS_OFFLINE=1 à l'exécution).
"""

from __future__ import annotations

import os
from typing import Protocol

DEFAULT_MODEL = "intfloat/multilingual-e5-small"


class Embedder(Protocol):
    name: str
    dimension: int

    def embed(self, texts: list[str], kind: str) -> list[list[float]]: ...


class SentenceTransformerEmbedder:
    """Enveloppe de sentence-transformers (préfixes « query: » / « passage: » des modèles e5)."""

    name: str
    dimension: int

    def __init__(self, name: str | None = None) -> None:
        from sentence_transformers import SentenceTransformer

        self.name = name or os.environ.get("EMBEDDINGS_MODEL") or DEFAULT_MODEL
        self._model = SentenceTransformer(self.name, device="cpu")
        dimension = self._model.get_sentence_embedding_dimension()
        self.dimension = int(dimension or 0)

    def embed(self, texts: list[str], kind: str) -> list[list[float]]:
        if not texts:
            return []

        prefix = "passage: " if kind == "passage" else "query: "
        vectors = self._model.encode(
            [prefix + text for text in texts],
            normalize_embeddings=True,
            batch_size=16,
            show_progress_bar=False,
        )
        return [[float(value) for value in vector] for vector in vectors]
