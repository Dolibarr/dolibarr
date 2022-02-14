COMPOSER_BIN_DIR := vendor/bin
PHPUNIT_ARGS = -c tests/phpunit.xml

test: phpunit-dep
	${COMPOSER_BIN_DIR}/phpunit ${PHPUNIT_ARGS}

phpunit-dep:
	test -f ${COMPOSER_BIN_DIR}/phpunit || ( \
		echo "phpunit is required to run tests." \
			"Please run: composer install" >&2 && \
		exit 1 \
	)

# Requires:
# - Docker: https://docker.com
# - act: https://github.com/nektos/act
local-ci:
ifeq (, $(shell which act))
define ACT_ERROR
Consider running the following to install 'act':

   curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

The dependency 'act' was not found
endef
$(error ${ACT_ERROR})
endif
	act -P ubuntu-latest=shivammathur/node:latest -W .github/workflows/ci.yml

.SILENT:
.PHONY: test phpunit-dep local-ci
