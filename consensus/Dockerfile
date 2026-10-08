# Service d'embeddings. Le modèle est téléchargé UNE FOIS au build (depuis Hugging Face) ;
# à l'exécution, le service tourne hors ligne et ne contacte aucun hôte externe.
FROM python:3.12-slim AS base

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    HF_HOME=/opt/models \
    SENTENCE_TRANSFORMERS_HOME=/opt/models

WORKDIR /srv

COPY requirements.txt .
RUN pip install --no-cache-dir torch --index-url https://download.pytorch.org/whl/cpu \
    && pip install --no-cache-dir -r requirements.txt

ARG EMBEDDINGS_MODEL=intfloat/multilingual-e5-small
ENV EMBEDDINGS_MODEL=${EMBEDDINGS_MODEL}
RUN python -c "from sentence_transformers import SentenceTransformer; SentenceTransformer('${EMBEDDINGS_MODEL}', device='cpu')" \
    && chmod -R a+rX /opt/models

COPY app ./app

# Hors ligne à l'exécution : aucun appel sortant, même si le cache était incomplet.
ENV TRANSFORMERS_OFFLINE=1 \
    HF_HUB_OFFLINE=1 \
    TOKENIZERS_PARALLELISM=false

RUN useradd --system --uid 1001 --no-create-home svc
USER svc

EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8000/health').read()" || exit 1

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "1", "--no-access-log"]
