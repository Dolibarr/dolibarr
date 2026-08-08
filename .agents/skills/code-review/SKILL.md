---
name: code-review
description: >
  Reviews Dolibarr PHP code for compliance with coding standards and security best practices, and fixes identified issues. Use when the user asks to review, audit, fix, or update code for Dolibarr, or mentions code quality, security vulnerabilities, or PSR-12 compliance.
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
---

# Skill: Review Dolibarr Code and Fix Bad Practices

## When to Use This Skill

Use this skill whenever the user asks to review, audit, or fix Dolibarr code to match best practices.

## Inputs

The user request should contain, when available:

- a module name
- or a directory name
- or a file name

## General Rules

- follow the coding style already used in files in the module builder template at `htdocs/modulebuilder/templates`
- modify the minimum amount of existing code

## Rules

- use PSR-12 coding style except for indentation, which must use TAB characters and not spaces
- remove all spaces at the end of lines
- rewrite all non-English code comments in English
- scan files for security vulnerabilities

## Output

When generating code:

- provide only the relevant PHP code
- preserve the existing file formatting
- do not rewrite unrelated methods
- explain briefly what is being fixed

## Examples

### Input: "Review the supplier invoice module for security issues"

**Action:**
1. scan `htdocs/fournisseur/` directory for common vulnerabilities
2. check for unescaped SQL queries
3. verify all user inputs use `GETPOST()` with type parameters
4. ensure HTML output is escaped with `dolPrintHTML()` or `dolPrintHTMLForAttribute()`

### Input: "Fix coding style in htdocs/core/lib/functions.lib.php"

**Action:**
1. review file against PSR-12 standards (with TAB exception)
2. remove trailing whitespace
3. convert non-English comments to English
4. apply consistent formatting

## Error Handling

### Common Failures and Validation

| Issue | Validation | Solution |
|-------|------------|----------|
| File not found | Verify path exists | Check module structure and file location |
| Syntax errors after fix | Run PHP lint | Roll back and reapply changes carefully |
| Breaking existing functionality | Run existing tests | Verify tests pass before and after changes |
| False positives in security scan | Manual verification | Cross-check with Dolibarr security guidelines |
| Mixed line endings | Check with `cat -A` | Normalize to LF |

**Before applying fixes:**
- back up the original file
- verify the file is not part of a protected core module
- run existing tests to establish a baseline
- apply changes incrementally

## Gotchas

- **Dolibarr conventions override PSR-12**: Tabs must be used for indentation, not spaces, even though PSR-12 recommends spaces
- **Legacy code**: Some older modules cannot be fully PSR-12 compliant. Prioritize consistency with existing module style
- **Global variables**: Dolibarr uses globals like `$db`, `$conf`, `$user`. Do not remove these without understanding the architecture
- **Dolibarr functions**: Prefer built-in Dolibarr functions (e.g., `dol_print_date()`, `getDolGlobalString()`) over native PHP functions
- **SQL injection**: Dolibarr has its own sanitizing and escaping methods (`$db->escape()`, casting to `(int)`, `$db->sanitize()`). Do not replace with prepared statements without testing
- **XSS protection**: Use `dolPrintHTML()`, `dolPrintHTMLForAttribute()`, or `dol_htmlentities()` for output, not native `htmlentities()`
- **CSRF tokens**: All POST forms must include `<input type="hidden" name="token" value="'.newToken().'">`
