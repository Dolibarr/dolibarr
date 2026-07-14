---
name: add-unit-test
description: Add a unit test to test functions or methods.
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
---
 
 
# Skill: Add a Unit Test for Dolibarr

## When to use this skill

Use this skill whenever the user asks to create, modify, or complete a **PHP unit test** for the Dolibarr ERP/CRM project.

The goal is to produce a unit test that follows Dolibarr conventions and integrates cleanly into the existing PHPUnit test suite.

## General Rules

- Follow the coding style already used in files into directory test/phpunit/
- Modify the minimum amount of existing code.
- Prefer adding new test methods instead of modifying existing ones.
- Tests must be deterministic and independent.
- Avoid dependencies on external services.
- Clean up every object created during the test.

## Test Location

Locate the most appropriate existing PHPUnit test file in test/phpunit.

If no suitable test exists, create one in:

```
test/phpunit/
```

using the naming convention:

```
FeatureTest.php
```


## Naming

A test method should describe the expected behavior.

Examples:

```php
public function testCreateObject()
public function testDeleteObject()
public function testFetchReturnsExpectedValues()
public function testInvalidInputThrowsException()
```

## Assertions

Prefer specific assertions.

Good:

```php
$this->assertTrue($result);
$this->assertFalse($result);
$this->assertEquals($expected, $actual);
$this->assertCount(3, $array);
$this->assertNull($value);
$this->assertNotNull($value);
```

Avoid generic assertions when a more precise one exists.


## Output

When generating code:

- provide only the relevant PHP code
- preserve the existing file formatting
- do not rewrite unrelated methods
- explain briefly what is being tested

