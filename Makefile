.DEFAULT_GOAL := help

EXEC = docker compose exec app

.PHONY: help up down build restart dev shell logs migrate seed fresh test lint lint-check artisan composer npm

help: ## List available commands
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Start containers in the background
	@docker compose up -d

down: ## Stop containers
	@docker compose down

build: ## Build container images
	@docker compose build

restart: ## Restart the app container (reloads php artisan dev)
	@docker compose restart app

dev: ## Start containers and follow php artisan dev output (server, queue, logs, vite)
	@docker compose up -d
	@docker compose logs -f app

shell: ## Open a bash shell in the app container
	@$(EXEC) bash

logs: ## Tail app container logs
	@docker compose logs -f app

migrate: ## Run database migrations
	@$(EXEC) php artisan migrate

seed: ## Seed the database
	@$(EXEC) php artisan db:seed

fresh: ## Drop all tables, migrate and seed
	@$(EXEC) php artisan migrate:fresh --seed

test: ## Run the test suite
	@$(EXEC) php artisan test

lint: ## Fix code style with Pint
	@$(EXEC) ./vendor/bin/pint

lint-check: ## Check code style with Pint without changing files
	@$(EXEC) ./vendor/bin/pint --test

artisan: ## Run an artisan command, e.g. make artisan cmd="route:list"
	@$(EXEC) php artisan $(cmd)

composer: ## Run a composer command, e.g. make composer cmd="require foo/bar"
	@$(EXEC) composer $(cmd)

npm: ## Run an npm command, e.g. make npm cmd="install foo"
	@$(EXEC) npm $(cmd)
