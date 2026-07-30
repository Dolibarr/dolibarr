---
name: add-unit-test
description: >
  Creates PHP unit tests for Dolibarr ERP/CRM functions and methods. Use when the user asks to add, write, create, or complete a PHPUnit test for Dolibarr, or mentions testing a specific function, method, or class in the Dolibarr codebase.
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
---

# Skill: Add a Unit Test for Dolibarr

## When to Use This Skill

Use this skill whenever the user asks to create, modify, or complete a PHP unit test for the Dolibarr ERP/CRM project.

The goal is to produce a unit test that follows Dolibarr conventions and integrates cleanly into the existing PHPUnit test suite.

## Inputs

The user request should contain, when available:

- a name of the function to test
- or the class method name to test

## General Rules

- follow the coding style already used in files in the `test/phpunit/` directory
- modify the minimum amount of existing code
- prefer adding new test methods instead of modifying existing ones
- tests must be deterministic and independent
- avoid dependencies on external services
- clean up every object created during the test

## Test Location

Locate the most appropriate existing PHPUnit test file in `test/phpunit/`.

If no suitable test file exists, create one using the naming convention `FeatureTest.php`.

## Naming

A test method should describe the expected behavior.

**Examples:**

```php
public function testCreateObject()
public function testDeleteObject()
public function testFetchReturnsExpectedValues()
public function testInvalidInputThrowsException()
```

## Assertions

Prefer specific assertions over generic ones.

**Good examples:**

```php
$this->assertTrue($result);
$this->assertFalse($result);
$this->assertEquals($expected, $actual);
$this->assertCount(3, $array);
$this->assertNull($value);
$this->assertNotNull($value);
```

## Output

When generating code:

- provide only the relevant PHP code
- preserve the existing file formatting
- do not rewrite unrelated methods
- explain briefly what is being tested

## Examples

### Input: "Add a unit test for the create() method of the Invoice class"

**Action:**
1. locate or create `test/phpunit/InvoiceTest.php`
2. add test method following Dolibarr conventions

### Input: "Write tests for the calculateVAT() function in price.lib.php"

**Action:**
1. locate or create appropriate test file in `test/phpunit/`
2. add test methods for various VAT calculation scenarios

## Error Handling

### Common Failures and Validation

| Issue | Validation | Solution |
|-------|------------|----------|
| Test file does not exist | Check `test/phpunit/` directory | Create new test file with proper naming |
| Class or method not found | Verify namespace and file location | Use proper use statements and class paths |
| Database dependencies | Review test for external DB calls | Mock database interactions or use test fixtures |
| Non-deterministic behavior | Check for random values or timestamps | Use fixed seeds or mock time |
| Missing PHPUnit | Check project dependencies | Ensure PHPUnit is installed via Composer |

**Before generating code:**
- verify the target function or method exists and is accessible
- confirm the test directory structure matches Dolibarr conventions
- ensure no external service dependencies exist in the code under test

## Gotchas

- **Dolibarr global variables**: Tests may need `$conf`, `$db`, `$user` mocks. Use `DolibarrTestCase` if available
- **Entity filtering**: Add `entity IN ('.getDolEntity('tablename').')` to SQL in tests if needed
- **Permissions**: Some methods check `$user->hasRight()`. Mock user permissions in tests
- **File paths**: Use `DOL_DOCUMENT_ROOT` constant for paths, not hardcoded values
- **Legacy code**: Older modules may not have test files. Create new ones following current conventions
