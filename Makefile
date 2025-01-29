help: 		## Display this message
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

install: build up

.PHONY: help install

##@ Docker stack

build: 	## Build project images
	@docker compose pull --parallel --quiet --ignore-pull-failures 2> /dev/null
	@docker compose build --pull

down: 	## Stops project containers
	@docker compose down -v --remove-orphans

kill:
	@docker compose kill
	@$(MAKE) down

up: 	## Starts project
	@docker compose up -d

.PHONY: build down kill up
