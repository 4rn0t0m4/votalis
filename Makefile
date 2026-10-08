COMPOSE = docker compose -f infra/compose.dev.yml --env-file app/.env

.PHONY: help up down logs shell test lint stan hooks fresh a11y load bench-votes backup-test audit-deps

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

a11y: ## Audit d'accessibilité automatisé (pa11y, axe) des 5 pages principales sur http://localhost:8080
	cd app && npx pa11y-ci --config .pa11yci.json

load: ## Test de charge k6 des pages publiques et du vote rapide (conteneur grafana/k6 sur le réseau du compose)
	docker run --rm -i --network votalis_default -v $(PWD)/infra/load:/scripts grafana/k6 run -e BASE_URL=http://web /scripts/public.js
	docker run --rm -i --network votalis_default -v $(PWD)/infra/load:/scripts grafana/k6 run -e BASE_URL=http://web /scripts/quick-vote.js

bench-votes: ## Latence serveur de l'enregistrement des votes (hors production)
	$(COMPOSE) exec app php artisan votalis:bench-votes --count=500

backup-test: ## Sauvegarde chiffrée puis restauration dans votalis_restore_test (BACKUP_PASSPHRASE_FILE requis)
	./infra/backup/backup.sh infra/compose.dev.yml app/.env /tmp/votalis-backups
	./infra/backup/restore.sh infra/compose.dev.yml app/.env "$$(ls -t /tmp/votalis-backups/votalis-*.enc | head -1)"

audit-deps: ## Audit des dépendances (composer, npm, pip)
	cd app && composer audit && npm audit --omit=dev --audit-level=high
	docker run --rm -v $(PWD)/consensus:/src python:3.12-slim sh -c "pip install -q pip-audit && pip-audit -r /src/requirements.txt"
