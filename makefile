# Initial variables
os ?= $(shell uname -s)

# Load custom setitngs
-include .env
export
PROVISION ?= docker
include etc/$(PROVISION)/makefile

install i: | build updatedb exportdb.schema index test open ## Perform install tasks (build, updatedb, index, test, and open). This is the default task

debug: | build xdebug updatedb exportdb.schema index test open ## Perform install tasks (build, updatedb, index, test, and open).

tag: ## Tag and push current branch. Usage make tag version=<semver>
	git tag -a $(version) -m "Version $(version)"
	git push origin $(version)

squash: branch := $(shell git rev-parse --abbrev-ref HEAD)
squash:
	git rebase -i $(shell git merge-base origin/$(branch) origin/master)
	git push -f

publish: container ?= app
publish: environment ?= Production
#publish: test release checkoutlatesttag deployimage
publish: ## Tag and deploy version. Registry authentication required. Usage: make publish
	make updateservice

preview review: container ?= app
preview review: version := $(shell git rev-parse --abbrev-ref HEAD)
preview review: | build test ## Tag, deploy and push image of the current branch. Update service. Registry authentication required. Usage: make review
	make deployimage
	make updateservice

push: branch := $(shell git rev-parse --abbrev-ref HEAD)
push: ## Review, add, commit and push changes using commitizen. Usage: make push
	git diff
	git add -A .
	@docker run --rm -it -e CUSTOM=true -v $(CURDIR):/app -v $(HOME)/.gitconfig:/root/.gitconfig aplyca/commitizen
	git pull origin $(branch)
	git push -u origin $(branch)

checkoutlatesttag:
	git fetch --prune origin "+refs/tags/*:refs/tags/*"
	git checkout $(shell git describe --always --abbrev=0 --tags)

updatedb: ## Sync DB Schema from Doctrine entities
	make run command="bin/console doctrine:schema:update --force --complete --dump-sql"

exportdb: exportdb.data exportdb.schema ## Export DB Schema and Data in separated files

exportdb.data: ## Export DB data
	make exec container=db command="pg_dump -U app --no-owner --data-only --blobs --column-inserts --inserts --schema=public app > /docker-entrypoint-initdb.d/01-data.sql"

exportdb.schema: ## Export DB Schema
	make exec container=db command="pg_dump -U app --no-owner --schema-only app > /docker-entrypoint-initdb.d/00-schema.sql"

index: ## Index/reindex DB content in ElasticSearch
	make run command="until curl -sf 'http://search:9200' > /dev/null; do sleep 1; done && \
	bin/console ongr:es:index:create --if-not-exists && \
	bin/console dap:reindex"

import:	## Importing Records from JSON file
	make run command="bin/console dap:import '/tmp/app/etc/example-import.json'"

importdb: importdb.schema importdb.data

importdb.schema: ##import database schema from file
	make exec container=db command="psql -U app app < /docker-entrypoint-initdb.d/00-schema.sql"

importdb.data: ##import database from file
	make exec container=db command="psql -U app app < /docker-entrypoint-initdb.d/01-data.sql"

test: start codecept behat ## Run all tests.

h help: ## This help.
	@echo 'Usage: make <task>'
	@echo 'Default task: install'
	@echo
	@echo 'Tasks:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9., _-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := install
.PHONY: all
