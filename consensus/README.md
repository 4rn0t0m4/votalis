# Service d'embeddings (et consensus en V2)

Service Python interne (FastAPI) qui calcule les embeddings des propositions avec un modèle multilingue open source auto-hébergé (`intfloat/multilingual-e5-small`, 384 dimensions). Il n'est jamais exposé à Internet : Laravel l'appelle via le réseau Docker privé.

## Points d'entrée

- `GET /health` : état, nom du modèle, dimension.
- `POST /embed` `{"texts": ["…"], "kind": "query"|"passage"}` : vecteurs normalisés (64 textes de 4 000 caractères au maximum par appel). Pour la similarité entre propositions, on utilise `query` des deux côtés, comme le recommandent les auteurs du modèle.

Aucun texte n'est stocké ni journalisé.

## Souveraineté

Le modèle est téléchargé une seule fois, au build de l'image, depuis Hugging Face ; à l'exécution, `TRANSFORMERS_OFFLINE=1` interdit tout appel sortant. En production, l'image est reconstruite et poussée sur le registre de l'hébergeur européen.

## Développement

```sh
docker compose -f infra/compose.dev.yml --env-file app/.env up -d --build embeddings
curl -s localhost:8001/health
```

Tests, style et typage (dans un conteneur, Python 3.12 n'étant pas requis sur l'hôte) :

```sh
docker run --rm -v "$PWD/consensus:/srv" -w /srv python:3.12-slim sh -c "pip install -q -r requirements-dev.txt && ruff check . && mypy && pytest -q"
```

Le calcul de consensus par familles de votants (cahier des charges, section 5) rejoindra ce service en V2.
