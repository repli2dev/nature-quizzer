# Export all variables from .env file to Makefile variables
include .env
$(eval export $(shell sed -ne 's/ *#.*$$//; /./ s/=.*$$// p' .env))

.PHONY: up

# If the first argument is "import-database"...
ifeq (import-database,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "run"
  RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(RUN_ARGS):;@:)
endif

# If the first argument is "import-database"...
ifeq (ssh,$(firstword $(MAKECMDGOALS)))
  # use the rest as arguments for "run"
  RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  # ...and turn them into do-nothing targets
  $(eval $(RUN_ARGS):;@:)
endif


up: new-cert
	docker-compose up -d
	echo "URL: https://www.poznavackaprirody.test"
	source .env && echo "Adminer: http://$(IP):88 (nature-quizzer/nature-quizzer)"
	echo "⚠️ Do not forget to make import-database <BACKUP> on first run"
	echo "⚠️ Do not forget to make import-col on first run"

start:
	docker-compose start

stop:
	docker-compose stop

down:
	docker-compose down

ssh:
	docker-compose exec $(RUN_ARGS) bash

new-cert:
	cd .docker/nginx-certs; \
	openssl \
        req \
        -nodes \
        -newkey rsa:2048 \
        -keyout poznavackaprirody.test.key \
        -out poznavackaprirody.test.csr \
        -subj "/C=CZ/ST=/L=Brno/O=Private/OU=Leisure/CN=*.poznavackaprirody.test/emailAddress=jan@drabek.cz"; \
	openssl x509 -req -days 3650 -in poznavackaprirody.test.csr -signkey poznavackaprirody.test.key -out poznavackaprirody.test.crt

import-database:
	echo "⚠️ Ensure that the provided path is a relative path to a folder with backup and is within the project root!"
	echo "⚠️ This may take a while!"
	docker-compose exec php php /code/utils/backup.php restore /code/$(RUN_ARGS)

import-col:
	bzcat "$(PACKAGES_PATH)/sources/col.sql.bz2" | docker-compose exec -T db psql -U nature-quizzer nature-quizzer && cat "$(PACKAGES_PATH)/sources/col.customization.sql" | docker-compose exec -T db psql "dbname=nature-quizzer options=--search_path=col" -U nature-quizzer;

stan:
	docker compose exec php bash -c 'cd /code; php composer.phar stan'
