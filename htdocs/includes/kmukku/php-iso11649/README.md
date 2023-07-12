php-iso11649
=============

ISO 11649:2009 RF creditor reference library for PHP 

Inspired by nruotsal/node-iso11649.


## Installation

 php composer require kmukku/php-iso11649:dev-master

### Generating RF creditor reference

RF creditor reference can be generated from existing reference.

Existing reference characteristics:
 * Contain only numbers 0-9 and/or characters A-Z (example AB2G5 => RF68 AB2G 5).
 * Max length 21 characters.
 * Not case sensitive (example aB2g5 => RF68 AB2G 5).
 * Can be string with spaces (example '12345 12345' => RF45 1234 5123 45).

```
  use kmukku\phpIso11649\phpIso11649;

  $referenceGenerator = new phpIso11649();
  echo $referenceGenerator->generateRfReference('1234512345',true);
  // => RF45 1234 5123 45

  echo $referenceGenerator->generateRfReference('1234512345',false);
  // => RF451234512345
```

### Validating RF creditor reference

Valid RF creditor reference characteristics:
 * Must start with characters RF.
 * Must contain two checksum numbers in indexes 3 and 4.
 * Max length 25 characters.
 * Reference part must follow rules described in 'Existing reference characteristics' section.

```
  use kmukku\phpIso11649\phpIso11649;

  $referenceGenerator = new phpIso11649();
  $referenceGenerator->validateRfReference('RF45 1234 5123 45');
  // => true
```

## Release History

* 1.0.0
    - Initial release