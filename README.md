# Plateforme de débat citoyen

Plateforme web permanente et non partisane : des mesures concrètes pour la France, que chacun peut consulter, voter, arbitrer, débattre et proposer. Le nom public n'est pas encore choisi ; « votalis » est le nom de code du dépôt.

- Cahier des charges : document de référence du projet (lien privé).
- [Architecture](docs/architecture.md) · [Classement](docs/classement.md) · [Plans par lot](docs/plans/)
- Licence [AGPL v3](LICENSE) · [Contribuer](CONTRIBUTING.md) · [Sécurité](SECURITY.md)

## Démarrage

Prérequis : Docker, PHP 8.3+ et Composer sur la machine hôte pour les commandes Artisan (ou passer par `make shell`).

```sh
cp app/.env.example app/.env
cd app && composer install && php artisan key:generate && php artisan votalis:hash-key && cd ..
make up
cd app && php artisan migrate --seed && npm install && npm run build
```

Le site répond sur http://localhost:8080, les e-mails de développement sur http://localhost:8025 (Mailpit), Meilisearch sur http://localhost:7700, le service d'embeddings sur http://localhost:8001/health. Première construction de l'image d'embeddings : plusieurs minutes (téléchargement du modèle, environ 500 Mo).

Après un import ou un seed : `php artisan scout:sync-index-settings && php artisan scout:import "App\\Models\\Proposal" && php artisan proposals:embed`.

Comptes de développement créés par le seeder : `participante`, `moderateur`, `comite`, `admin` (mot de passe `mot-de-passe-de-test-123`, TOTP de test pour les rôles privilégiés : secret `JBSWY3DPEHPK3PXP`).

## Mode de production du code

Ce code a été écrit avec un assistant d'IA (Claude, Anthropic), à partir d'un cahier des charges rédigé par l'auteur du projet, lot par lot, chaque lot ayant fait l'objet d'un plan validé, de tests écrits avec le code et d'une relecture par pull request. Chaque commit porte la mention `Co-Authored-By`. Le dépôt a été déplacé de Codeberg vers GitHub le 8 octobre 2026, les conditions d'utilisation de Codeberg excluant ce mode de production (voir `docs/architecture.md`, décisions).

## Qualité

```sh
make test   # PHPUnit sur PostgreSQL
make lint   # Pint
make stan   # PHPStan niveau 8
make hooks  # pre-commit : gitleaks + Pint
```

## Exploitation

- Déploiement, sauvegardes, lecture seule et tenue en charge : `docs/exploitation.md`.
- Sécurité (auto-évaluation ASVS, dossier d'audit) : `docs/securite.md` ; signalement d'une faille : `SECURITY.md`.
- Données personnelles : `docs/rgpd/` (registre, AIPD) et pages `/confidentialite`, `/mentions-legales`, `/cookies`, `/accessibilite`.
- Commandes : `make a11y`, `make load`, `make bench-votes`, `make backup-test`, `make audit-deps`.
