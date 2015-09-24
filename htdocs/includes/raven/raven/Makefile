.PHONY: test

develop:
	composer install --dev
	make setup-git

cs:
	vendor/bin/php-cs-fixer fix --config-file=.php_cs --verbose --diff

cs-dry-run:
	vendor/bin/php-cs-fixer fix --config-file=.php_cs --verbose --diff --dry-run

test:
	vendor/bin/phpunit

setup-git:
	git config branch.autosetuprebase always
