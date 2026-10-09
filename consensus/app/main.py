"""Service d'embeddings et de consensus. API interne, jamais exposée à Internet."""

from __future__ import annotations

from collections.abc import AsyncIterator
from contextlib import asynccontextmanager
from typing import Literal

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

from app import consensus
from app.embedder import Embedder, SentenceTransformerEmbedder

MAX_TEXTS = 64
MAX_CHARS = 4000
MAX_VOTES = 2_000_000
MAX_PROPOSALS = 20_000


class EmbedRequest(BaseModel):
    texts: list[str] = Field(max_length=MAX_TEXTS)
    kind: Literal["query", "passage"] = "query"


class EmbedResponse(BaseModel):
    model: str
    dimension: int
    vectors: list[list[float]]


class ConsensusParams(BaseModel):
    min_votes_per_participant: int = Field(default=7, ge=1, le=1000)
    k_min: int = Field(default=2, ge=2, le=10)
    k_max: int = Field(default=5, ge=2, le=10)
    alpha: float = Field(default=1.0, gt=0, le=100)
    min_voters_per_group: int = Field(default=5, ge=1, le=10_000)
    seed: int = Field(default=20261009, ge=0, le=2**32 - 1)


class ConsensusRequest(BaseModel):
    """Votes [rang anonyme, proposition, souhaitable, nécessaire] ; réponses -1, 0 ou 1."""

    proposals: list[int] = Field(max_length=MAX_PROPOSALS)
    votes: list[tuple[int, int, int, int]] = Field(max_length=MAX_VOTES)
    params: ConsensusParams = ConsensusParams()


class GroupRateOut(BaseModel):
    label: str
    voters: int
    agree_rate: float
    necessary_rate: float
    represented: bool


class ProposalScoreOut(BaseModel):
    proposal: int
    score: float | None
    divisiveness: float | None
    groups: list[GroupRateOut]


class GroupOut(BaseModel):
    label: str
    size: int


class ConsensusResponse(BaseModel):
    algo_version: str
    status: Literal["computed", "insufficient"]
    participants: int
    k: int
    silhouette: float | None
    groups: list[GroupOut]
    proposals: list[ProposalScoreOut]


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

    @app.post("/consensus", response_model=ConsensusResponse)
    def compute_consensus(request: ConsensusRequest) -> ConsensusResponse:
        p = request.params
        if p.k_max < p.k_min:
            raise HTTPException(status_code=422, detail="k_max doit être au moins égal à k_min.")
        if len(set(request.proposals)) != len(request.proposals):
            raise HTTPException(status_code=422, detail="Proposition en double.")
        try:
            votes = [consensus.Vote(*vote) for vote in request.votes]
            result = consensus.compute(
                votes,
                request.proposals,
                consensus.Params(
                    min_votes_per_participant=p.min_votes_per_participant,
                    k_min=p.k_min,
                    k_max=p.k_max,
                    alpha=p.alpha,
                    min_voters_per_group=p.min_voters_per_group,
                    seed=p.seed,
                ),
            )
        except ValueError as error:
            raise HTTPException(status_code=422, detail=str(error)) from error

        # Rien n'est journalisé : ni votes ni résultats.
        return ConsensusResponse.model_validate(result, from_attributes=True)

    return app


app = create_app()
