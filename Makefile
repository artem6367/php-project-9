PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public tests
	composer exec --verbose phpstan

test:
	composer exec --verbose phpunit tests

install:
	composer install