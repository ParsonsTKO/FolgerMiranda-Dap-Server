requirements: ## Installing local requirements
ifeq ($(os), Darwin)
	grep -q 'brew' <<< echo `command -v brew` || /usr/bin/ruby -e "`curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install`"
	brew install make git
	brew cask install docker
	open -a Docker
else ifeq ($(os), Linux)
	sudo sh -c "$$(curl -fsSL https://get.docker.com)"
	sudo apt-get install make git
	sudo usermod -aG docker $${USER}
	sudo service docker start
	sudo curl -L https://github.com/docker/compose/releases/download/1.22.0/docker-compose-$(os)-$(shell uname -m) -o /usr/local/bin/docker-compose
	sudo chmod +x /usr/local/bin/docker-compose
	su - $${USER}
else ifeq ($(os), Windows_NT)
	@"%SystemRoot%\System32\WindowsPowerShell\v1.0\powershell.exe" -NoProfile -InputFormat None -ExecutionPolicy Bypass -Command "iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))" && SET "PATH=%PATH%;%ALLUSERSPROFILE%\chocolatey\bin"
	choco install make git docker-for-windows -y
else
	$(warning Platform "$(os)" not supported)
endif

ifdef command
override command := -c "$(command)"
endif

ifdef profile
override profile := --profile $(profile)
endif

ifeq ($(os), Darwin)
open = open
else ifeq ($(os), Linux)
open = xdg-open
else ifeq ($(os), Windows_NT)
open =  explorer
endif

build: container ?=
build:
	docker-compose up --build -d --remove-orphans $(container)

remove:
	docker-compose rm --force $(container)

reload up: ## Reload containers 
	docker-compose up -d $(container)

down:
	docker-compose down $(container)

stop:
	docker-compose stop $(container)

start: container ?=
start:
	docker-compose start $(container)

restart:
	docker-compose restart $(container)

rebuild: | down build ## Rebuild containers

reboot: | remove up ## Recreate containers

status ps:
	docker-compose ps $(container)

cli exec: container ?= app
cli exec: ## Execute commands in containers, use "command"  argument to send the command. By Default enter the shell.
	docker-compose exec $(container) /bin/ash $(command)

run: container ?= app
run: ## Run commands in a new container
	docker-compose run --rm $(container) /bin/ash $(command)

config:
	docker-compose config

logs: container ?= app
logs: ## Show logs. Usage: make logs
	docker-compose logs -f $(container)

copy: container ?= app
copy: ## Copy app files/directories from container to host
	docker cp $(shell docker-compose ps -q $(container)):$(path) .

sync: ## Copy composer files generated inside container to host
	make copy path=/app/composer.json
	make copy path=/app/composer.lock

sync.vendor: sync ## Copy vendor files generated inside app container to the host
	make copy path=/app/vendor

sync.all: sync sync.vendor ## Copy files generated inside container to host

open: ## Open web app in the browser
	$(open) $(subst 0.0.0.0,localhost,http://$(shell docker-compose port web 80))
	
open.mail: ## Open mail app in the browser
	$(open) $(subst 0.0.0.0,localhost,http://$(shell docker-compose port mail 8025))

open.db:
	$(open) $(subst 0.0.0.0,localhost,http://$(shell docker-compose port dbadmin 8080))

expose: ## Expose your local environment to the internet, thanks to Serveo (https://serveo.net)
	ssh -R 80:localhost:$(subst 0.0.0.0:,,$(shell docker-compose port web 80)) serveo.net

ifdef suite
behat: suite_arg := --suite="$(suite)" --format="pretty"
format ?=
endif

ifdef feature
behat: feature_arg := $(feature) --format="pretty"
format ?=
endif

behat: ## Testing with Behat. Usage: make test.behat suite="suite" feature="path"
	make exec container=app command="vendor/bin/behat $(suite_arg) $(feature_arg)"

codecept: format ?= --ext DotReporter
codecept: ## Testing with Codeception. Usage: make test.codecept suite="suite" feature="path"
	make exec container=app command="vendor/bin/codecept run $(suite) $(feature) $(format)"

ecslogin:
	$(shell docker run --rm -it --env AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID} --env AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY} infrastructureascode/aws-cli:1.16.23 ash -c "aws ecr get-login --no-include-email --region us-east-2")

release:
	git checkout production
	docker run --rm -it -v $(CURDIR):/app -v ~/.ssh:/root/.ssh -w /app aplyca/semantic-release ash -c "semantic-release --no-ci"
	git pull

updateservice: environment ?= Staging
updateservice: ## 🚀  Update service in ECS, default FOLGERDAP-Server-Staging
	$(info 🚀  Updating ECS service FOLGERDAP-Server-$(environment) ...) 
	@docker run --rm -it --env AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID} --env AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY} infrastructureascode/aws-cli ash -c "aws ecs update-service --cluster FOLGERDAP --service FOLGERDAP-Server-$(environment) --force-new-deployment --region us-east-2 --query 'service.{status:status,pendingCount:pendingCount,desiredCount:desiredCount,runningCount:runningCount,serviceName:serviceName,taskDefinition:taskDefinition}'"

deployimage: container ?= app
deployimage: registryurl ?= 159895783284.dkr.ecr.us-east-2.amazonaws.com/folgerdap/server/$(container)
deployimage: version ?= $(shell git describe --always --abbrev=0 --tags)
deployimage: ecslogin ## 🐳  Login to Registry, build, tag and push the images. Registry authentication required. Usage: make deployimage version="<semver>". Use version=latest to create the latest image
	$(info 🐳  Pushing version '$(version)' of the '$(container)' Docker image ...) 
	docker build --target prod -t $(registryurl):$(version) -f etc/docker/$(container)/Dockerfile .
	docker push $(registryurl):$(version)

deploylatestimage: version ?= $(shell git describe --always --abbrev=0 --tags)
deploylatestimage: ## Login to Registry, build, tag with the latest images and push to registry. Registry authentication required. Usage: make deploylatestimage version="<semver>"
	docker tag $(registryurl):$(version) $(registryurl):latest
	docker push $(registryurl):latest

xdebug:
	docker-compose exec app /bin/ash -c 'apk --update --no-cache add $$PHPIZE_DEPS'
	docker-compose exec app /bin/ash -c 'yes | pecl install -f xdebug'
	docker-compose exec app /bin/ash -c 'echo "zend_extension=$$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'echo "xdebug.remote_autostart=0" >> /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'echo "xdebug.remote_port=$(XDEBUG_PORT)" >> /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'echo "xdebug.idekey=$(XDEBUG_IDEKEY)" >> /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'echo "xdebug.remote_host=$(XDEBUG_HOST_IP)" >> /usr/local/etc/php/conf.d/xdebug.ini'
	docker-compose exec app /bin/ash -c 'kill -USR2 1'