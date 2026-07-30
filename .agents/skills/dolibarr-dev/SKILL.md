---
name: dolibarr-dev
description: Use when developing Dolibarr ERP/CRM code, working with database queries, or asking about Dolibarr best practices.
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
 - bash
---

# Dolibarr Development Best Practices

## When to use this skill

- When the user asks about Dolibarr coding standards
- When reviewing or writing code that interacts with the Dolibarr database
- When setting up development environment for Dolibarr
- When the user mentions SQL queries, database access, or code searching in Dolibarr

## Database Access

### SQL Statements

**Always use PHP with Dolibarr's database abstraction, never direct SQL CLI.**

Dolibarr provides database abstraction through the `$db` object. Always use PHP to execute SQL:

```php
// Correct - using Dolibarr's database methods
$result = $this->db->query("SELECT * FROM " . $this->db->prefix() . "actioncomm");

// Wrong - using direct SQL CLI
// mysql -e "SELECT * FROM llx_actioncomm"
```

### Table Prefixes

Use `$this->db->prefix()` instead of the `MAIN_DB_PREFIX` constant:

```php
// Correct:
$sql = "SELECT * FROM " . $this->db->prefix() . "actioncomm WHERE id = 1";

// Legacy (still works but not recommended):
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "actioncomm WHERE id = 1";
```

### Parameter Escaping

**Integer fields:** Use `(int)` casting
**Variable field names:** Use `$this->db->sanitize()`
**String fields:** Use `$this->db->escape()`

```php
$sql .= " AND a." . $this->db->sanitize($recurid_field) . " = '" . $this->db->escape($recurid) . "'" AND a.id = " . ((int) $id);
```

(When not in a class, use `$db`)


## Code Searching

### Use `git grep` for efficiency

`git grep -n` is more efficient than `grep`:

```bash
# Search in all public facing PHP files
git grep -nP 'function NAME\b' -- ":htdocs/*.php"
```

## Quality Assurance

### Pre-commit Hooks

When `pre-commit` is installed, use it to run tools (beautifiers, code quality checks):

```bash
if command -v pre-commit >/dev/null 2>&1; then
    pre-commit run --hook-stage manual shellcheck --file dev/pullmerge.sh
fi
```

**Typical Dolibarr pre-commit hooks:**
- `phpcs` - PHP Code Sniffer for PSR-12 compliance
- `phpcbf` - PHP Code Formatter for PSR-12 compliance
- `shellcheck` - Shell script Analysis Tool

## Coding Standards

- Follow PSR-12 coding style
- Use TAB characters for indentation (not spaces)
- Remove all spaces at end of lines
- Write all code comments in English, translate existing comments.
- Scan files for security vulnerabilities
