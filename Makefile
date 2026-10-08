COMPOSE = docker compose -f infra/compose.dev.yml --env-file app/.env

.PHONY: help up down logs shell test lint stan hooks fresh

help: ## Liste des commandes
	@grep -E '^[a-z]+:.*##' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*##"}; {printf "  %-10s %s\n", $$1, $$2}'

up: ## Démarre l'environnement de développement
	$(COMPOSE) up -d --build

down: ## Arrête les conteneurs
	$(COMPOSE) down

logs: ## Suit les journaux
	$(COMPOSE) logs -f

shell: ## Ouvre un shell dans le conteneur applicatif
	$(COMPOSE) exec app sh

test: ## Lance la suite de tests
	$(COMPOSE) exec app php artisan test

lint: ## Vérifie le style avec Pint (sans modifier)
	$(COMPOSE) exec app ./vendor/bin/pint --test

stan: ## Analyse statique PHPStan niveau 8
	$(COMPOSE) exec app ./vendor/bin/phpstan analyse --memory-limit=1G

fresh: ## Réinitialise la base de développement
	$(COMPOSE) exec app php artisan migrate:fresh --seed

hooks: ## Installe les hooks git (gitleaks, pint)
	git config core.hooksPath infra/hooks
	@echo "Hooks installés depuis infra/hooks."
