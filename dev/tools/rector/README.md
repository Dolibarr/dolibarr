### Refactoring code with [rector](https://getrector.com)


#### Installation

run in this folder

```shell
composer install
```
 #### Usage

##### To make changes (Add --dry-run for test mode only)
```shell
./vendor/bin/rector process --dry-run
```

##### To make changes on a given directory

```shell
./vendor/bin/rector process [--dry-run] ../../../htdocs/core/
```
