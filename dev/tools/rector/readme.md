### Refactoring code with [rector](https://getrector.com)


#### Installation

run in this folder

```shell
composer install
```
 #### Usage

##### To see change before apply
```shell
./vendor/bin/rector process --dry-run
```

##### To apply change

```shell
./vendor/bin/rector process
```

##### Run only for a directory

```shell
./vendor/bin/rector process ../../../htdocs/core/
```
