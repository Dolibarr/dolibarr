### Refactoring code with [rector](https://getrector.com)


#### Installation

Run in this folder
```shell
cd dev/tools/rector
```

Install rector with composer
```shell
composer install
```


#### Usage

##### To make changes (remove --dry-run for real run)
```shell
cd dev/tools/rector
./vendor/bin/rector process --debug --dry-run
```

##### To make changes on a given directory

```shell
cd dev/tools/rector
./vendor/bin/rector process --debug [--dry-run] [--clear-cache] ../../../htdocs/core/
```
