"""Tests de l'API avec un modèle factice : aucun téléchargement, aucune dépendance au réseau."""

from __future__ import annotations

import math

from fastapi.testclient import TestClient

from app.main import create_app


class FakeEmbedder:
    name = "fake-model"
    dimension = 3

    def embed(self, texts: list[str], kind: str) -> list[list[float]]:
        vectors = []
        for text in texts:
            raw = [float(len(text)), float(text.count("a")), 1.0 if kind == "passage" else 0.5]
            norm = math.sqrt(sum(v * v for v in raw))
            vectors.append([v / norm for v in raw])
        return vectors


def client() -> TestClient:
    return TestClient(create_app(FakeEmbedder()))


def test_health() -> None:
    with client() as c:
        response = c.get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok", "model": "fake-model", "dimension": 3}


def test_embed_returns_normalized_vectors_of_the_right_dimension() -> None:
    with client() as c:
        payload = {"texts": ["Baisser les charges", "Alléger les cotisations"]}
        response = c.post("/embed", json=payload)

    assert response.status_code == 200
    body = response.json()
    assert body["dimension"] == 3
    assert len(body["vectors"]) == 2
    for vector in body["vectors"]:
        assert len(vector) == 3
        assert abs(math.sqrt(sum(v * v for v in vector)) - 1.0) < 1e-6


def test_empty_batch_and_empty_text() -> None:
    with client() as c:
        assert c.post("/embed", json={"texts": []}).json()["vectors"] == []
        assert c.post("/embed", json={"texts": ["   "]}).status_code == 422


def test_too_many_texts_is_refused() -> None:
    with client() as c:
        response = c.post("/embed", json={"texts": ["x"] * 65})

    assert response.status_code == 422


def test_no_docs_exposed() -> None:
    with client() as c:
        assert c.get("/docs").status_code == 404
