"""Service d'embeddings (et, en V2, de consensus). API interne, jamais exposée à Internet."""

from __future__ import annotations

from collections.abc import AsyncIterator
from contextlib import asynccontextmanager
from typing import Literal

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from app.embedder import Embedder, SentenceTransformerEmbedder

MAX_TEXTS = 64
MAX_CHARS = 4000


class EmbedRequest(BaseModel):
    texts: list[str] = Field(max_length=MAX_TEXTS)
    kind: Literal["query", "passage"] = "query"


class EmbedResponse(BaseModel):
    model: str
    dimension: int
    vectors: list[list[float]]


class HealthResponse(BaseModel):
    status: str
    model: str
    dimension: int


def create_app(embedder: Embedder | None = None) -> FastAPI:
    state: dict[str, Embedder] = {}

    @asynccontextmanager
    async def lifespan(_: FastAPI) -> AsyncIterator[None]:
        current: Embedder = embedder if embedder is not None else SentenceTransformerEmbedder()
        state["embedder"] = current
        yield
        state.clear()

    app = FastAPI(title="Service d'embeddings", docs_url=None, redoc_url=None, lifespan=lifespan)

    @app.get("/health", response_model=HealthResponse)
    def health() -> HealthResponse:
        current = state["embedder"]
        return HealthResponse(status="ok", model=current.name, dimension=current.dimension)

    @app.post("/embed", response_model=EmbedResponse)
    def embed(request: EmbedRequest) -> EmbedResponse:
        current = state["embedder"]
        texts = [text.strip()[:MAX_CHARS] for text in request.texts]

        if any(text == "" for text in texts):
            raise HTTPException(status_code=422, detail="Un texte est vide.")

        # Aucun texte n'est journalisé : seule la taille du lot l'est par uvicorn.
        return EmbedResponse(
            model=current.name,
            dimension=current.dimension,
            vectors=current.embed(texts, request.kind),
        )

    return app


app = create_app()
