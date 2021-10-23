# How to contribute

This project is open to many different types of contribution. You can help with improving the documentation and examples, sharing your insights on the issue tracker, adding fixes to the code, providing test cases, or just [writing about your hardware setup that you use](https://github.com/mike42/escpos-php/issues/new).

## Issue tracker

Open issues of all sorts are tracked on the [issue tracker](https://github.com/mike42/escpos-php/issues). Please check [the FAQ](https://github.com/mike42/escpos-php/blob/development/doc/FAQ.md) before you post, and practice good [bug tracker etiquette](https://bugzilla.mozilla.org/page.cgi?id=etiquette.html) to keep it running smoothly.

Issues are [loosely categorised](https://github.com/mike42/escpos-php/labels), and will stay open while there is still something that can be resolved.

Anybody may add to the discussion on the bug tracker. Just be sure to add new questions as separate issues, and to avoid commenting on closed issues.

## Submitting changes

Code changes may be submitted as a "[pull request](https://help.github.com/articles/about-pull-requests/)" at [mike42/escpos-php](https://github.com/mike42/escpos-php). The description should include some information about how the change improves the library.

The project is MIT-licensed (see [LICENSE.md](https://github.com/mike42/escpos-php/blob/development/LICENSE.md) for details). You are not required to assign copyright in order to submit changes, but you do need to agree for your code to be distributed under this license in order for it to be accepted.

### Documentation changes

The official documentaton is also located in the main repository, under the [doc/](https://github.com/mike42/escpos-php/tree/development/doc) folder.

You are welcome to post any suggested improvements as pull requests.

### Release process

Once a pull request is accepted, it usually appears in a release a few days later.

Branches:

- "development" is the most recent code, possibly containing unreleased fixes
- "master" contains the most recently released code (old versions are not maintained).

The release process for your changes is:

- Changes are submitted via pull request to the shared "development" branch.
- A new release is staged on the "master" branch via another pull request, and then tagged.

## Code style

This project uses the [PSR-2 standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) for all PHP source code.

## Testing and CI

The tests are executed on [Travis CI](https://travis-ci.org/mike42/escpos-php) over PHP 5.4, 5.5, 5.6, 7.0, 7.1 and 7.2, plus the latest LTS version of HHVM, 3.21. Older versions of PHP are not supported in current releases.

For development, it's suggested that you load `imagick`, `gd` and `Xdebug` PHP exensions, and install `composer`.

Fetch a copy of this code and load dependencies with composer:

    git clone https://github.com/mike42/escpos-php
    cd escpos-php/
    composer install

Execute unit tests via `phpunit`:

    php vendor/bin/phpunit --coverage-text

Code style can be checked via [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer):

    php vendor/bin/phpcs --standard=psr2 src/ -n

The developer docs are built with [doxygen](https://github.com/doxygen/doxygen). Re-build them to check for documentation warnings:

    make -C doc clean && make -C doc
