# Contribuer

Merci de votre intérêt. Le code est public sous AGPL v3 ; seuls les mainteneurs fusionnent et déploient.

## Règles

1. Une pull request par changement, depuis une branche ou un fork. Aucun push direct sur `main`.
2. Une relecture approuvée et une CI verte sont requises pour fusionner.
3. Les tests sont écrits avec le code. On ne désactive jamais un test pour le faire passer.
4. Toute règle métier (plafonds, droits, fusion, modération) est appliquée côté serveur et couverte par un test.
5. Aucun secret, seuil anti-fraude ni valeur de production dans le dépôt : `.env.example` uniquement. gitleaks tourne en pre-commit et en CI.
6. Aucune dépendance ni service tiers hébergé hors d'Europe sans discussion préalable dans une issue.
7. Neutralité : pas de vocabulaire ni de visuel associé à un parti ou à une figure politique.

## Mise en route

```sh
cp app/.env.example app/.env
make up
make shell        # puis : composer install && php artisan key:generate && php artisan migrate
make hooks
make test
```

## Style

PHP : Laravel Pint (`make lint`) et PHPStan niveau 8 (`make stan`). Texte d'interface en français, chaînes externalisées dans `lang/`.

## Signalement de failles

Voir [SECURITY.md](SECURITY.md), jamais par issue publique.
