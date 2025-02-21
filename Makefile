-include .env

PHP = docker compose exec -u www-data app php
COMPOSER = $(PHP) /usr/local/bin/composer
CONSOLE = $(PHP) bin/console

.DEFAULT_GOAL := help

help: 				## Display this message
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

install: build up 	## Start project installation process

.PHONY: help install

##@ Docker stack

build: 				## Build project images
	@docker compose pull --parallel --quiet --ignore-pull-failures 2> /dev/null
	@docker compose build --pull

down: 				## Remove project containers
	-@docker network disconnect ${COMPOSE_PROJECT_NAME}_external ${LOCAL_PROXY_CONTAINER_NAME}
	@docker compose down -v --remove-orphans

kill:
	@docker compose kill
	@$(MAKE) down

stop: 				## Stop project containers
	@docker compose stop

up: 				## Start project containers
	@docker compose up -d
	-@docker network connect ${COMPOSE_PROJECT_NAME}_external ${LOCAL_PROXY_CONTAINER_NAME}

.PHONY: build down kill up

##@ Application

composer: 			## Shortcut to use Composer within app container
	@$(COMPOSER) ${c}

console: 			## Shortcut to use Symfony console within app container
	@$(CONSOLE) ${c}

.PHONY: composer console

##@ Testing / QA

phpunit: 			## Execute PHPUnit test suite
	@docker compose run --rm app php bin/phpunit

.PHONY: phpunit
