# PHP Parallel Lint
This tool check syntax of PHP files faster then serial check with fancier output.

Running parallel jobs in PHP inspired by Nette framework tests.


## Install
Just create a `composer.json` file and run the `php composer.phar install` command to install it:

```json
{
    "require-dev": {
        "jakub-onderka/php-parallel-lint": "0.*"
    }
}
```

For colored output install suggested package `jakub-onderka/php-console-highlighter`. After a `composer.json` file include:

```json
{
    "require-dev": {
        "jakub-onderka/php-parallel-lint": "0.*",
        "jakub-onderka/php-console-highlighter": "0.*"
    }
}
```


## Example output
![Alt text](/tests/examples/example-images/use-error.png?raw=true "Example use of tool with error")


## Options for run
- `-p <php>`        Specify PHP-CGI executable to run (default: 'php').
- `-s, --short`     Set short_open_tag to On (default: Off).
- `-a, -asp`        Set asp_tags to On (default: Off).
- `-e <ext>`        Check only files with selected extensions separated by comma. (default: php,php3,php4,php5,phtml)
- `--exclude`       Exclude directory. If you want exclude multiple directories, use multiple exclude parameters.
- `-j <num>`        Run <num> jobs in parallel (default: 10).
- `--no-colors`     Disable colors in console output.
- `--json`          Output results as JSON string (require PHP 5.4).
- `--blame`         Try to show git blame for row with error.
- `--git <git>`     Path to Git executable to show blame message (default: 'git').
- `--stdin`         Load files and folder to test from standard input.
- `--ignore-fails`  Ignore failed tests.
- `-h, --help`      Print this help.


## Recommended setting for usage with Symfony framework
For run from command line:

```
$ ./bin/parallel-lint --exclude app --exclude vendor .
```

or setting for ANT:

```xml
<condition property="parallel-lint" value="${basedir}/bin/parallel-lint.bat" else="${basedir}/bin/parallel-lint">
    <os family="windows"/>
</condition>

<target name="parallel-lint" description="Run PHP parallel lint">
    <exec executable="${parallel-lint}" failonerror="true">
        <arg line="--exclude" />
        <arg path="${basedir}/app/" />
        <arg line="--exclude" />
        <arg path="${basedir}/vendor/" />
        <arg path="${basedir}" />
    </exec>
</target>
```

## Create Phar package
PHP Parallel Lint supports [Box app](https://box-project.github.io/box2/) for creating Phar package. First, install box app:

```
curl -LSs https://box-project.github.io/box2/installer.php | php
```

and then run this command in parallel lint folder, which creates `parallel-lint.phar` file.

```
box build
```

------

[![Downloads this Month](https://img.shields.io/packagist/dm/jakub-onderka/php-parallel-lint.svg)](https://packagist.org/packages/jakub-onderka/php-parallel-lint)
[![Build Status](https://travis-ci.org/JakubOnderka/PHP-Parallel-Lint.svg?branch=master)](https://travis-ci.org/JakubOnderka/PHP-Parallel-Lint)
[![Build status](https://ci.appveyor.com/api/projects/status/5n3qqa3r66aoghjo/branch/master?svg=true)](https://ci.appveyor.com/project/JakubOnderka/php-parallel-lint/branch/master)
[![License](https://poser.pugx.org/jakub-onderka/php-parallel-lint/license.svg)](https://packagist.org/packages/jakub-onderka/php-parallel-lint)
