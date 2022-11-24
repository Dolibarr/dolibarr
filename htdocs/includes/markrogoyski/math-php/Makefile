.PHONY : lint tests style phpstan phpmd report coverage

all : lint tests style phpstan phpmd report

tests :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml

lint :
	vendor/bin/parallel-lint src tests

style :
	vendor/bin/phpcs --standard=tests/coding_standard.xml --ignore=vendor -s .

phpstan :
	vendor/bin/phpstan analyze --level max src/

phpmd :
	vendor/bin/phpmd src/ ansi cleancode,codesize,design,unusedcode,naming

coverage :
	vendor/bin/phpunit tests/ --configuration=tests/phpunit.xml --coverage-text=php://stdout

report :
	vendor/bin/phploc src/