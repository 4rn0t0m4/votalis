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

Le site répond sur http://localhost:8080, les e-mails de développement sur http://localhost:8025 (Mailpit).

Comptes de développement créés par le seeder : `participante`, `moderateur`, `comite`, `admin` (mot de passe `mot-de-passe-de-test-123`, TOTP de test pour les rôles privilégiés : secret `JBSWY3DPEHPK3PXP`).

## Qualité

```sh
make test   # PHPUnit sur PostgreSQL
make lint   # Pint
make stan   # PHPStan niveau 8
make hooks  # pre-commit : gitleaks + Pint
```
