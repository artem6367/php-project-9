PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src templates
	composer exec --verbose phpstan -- --memory-limit=-1

test:
	composer exec --verbose phpunit tests

install:
	composer install