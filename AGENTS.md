# AGENTS.md (English Version)

## 🎯 Objective

This project contains the full sources of the Dolibarr ERP and CRM application.
Every modification must respect:
- Dolibarr's modular architecture
- Compatibility with upstream updates
- Modern PHP best practices

---

## ⚠️ Critical Rules (DO NOT VIOLATE)

- ❌ Do not break compatibility of PHP functions and methods
- ❌ Do not introduce external dependencies without validation
- ❌ Separate page actions in the `/* Actions */` section of the PHP code and the rendering part in the `/* Views */` section
- ❌ Never commit directly to `main` or `master` branch
- ❌ Never use PHP native curl functions to call a GET or POST URL, but use instead the Dolibarr function getURLContent()
- ✅ Use Dolibarr hooks whenever possible
- ✅ Respect existing naming conventions
- ✅ All database table names must use the `llx_` prefix

---

## 📁 Expected Architecture

Module structure:
`htdocs/mymodule`
├── `core/`
├── `class/`
├── `lib/`
├── `sql/`
├── `tpl/`
└── `admin/`

A template of a module directory content can be found in the `htdocs/modulebuilder/template` folder of this project.

---

## 🔍 Before Coding

Before writing any code, the agent **must**:
- Search for existing similar functions in `htdocs/core/lib/` and `htdocs/core/class/`
- Check if the concerned object class extends `CommonObject` and use its built-in methods (fetch, create, update, delete, etc.)
- Review the module's `modMyModule.class.php` for declared permissions and constants
- Run a search to ensure no equivalent function already exists in the codebase

---

## 🧠 PHP Best Practices

- PHP >= 7.3 (minimum support); PHP 8.1+ recommended for new external modules
- ⚠️ When writing a **bug fix**, always target the lowest compatible PHP version
  of the branch being patched — do not use PHP 8.x syntax on a fix targeting v19 or v20
- Use `declare(strict_types=1)` when targeting PHP 8.1+
- Respect PSR-12, but **indentations must use Tabs, not Spaces**
- Write short, readable, and testable functions
- Avoid side effects
- Prefer typed properties and return types when PHP version allows

---

## 🗄️ Database

- Use Dolibarr database functions exclusively — never use PDO or MySQLi directly
    - In pages: use global `$db`
    - In classes: use `$this->db`
- ✅ Always escape user inputs
- ✅ SQL forged by PHP must escape fields with `db->escape()`, `db->sanitize()`, or by casting values to `(int)` or `(float)`
- ✅ Always use `$db->query()` followed by `$db->fetch_object()` or `$db->fetch_array()` to retrieve results
- ✅ SQL scripts for table and index creation must be placed in `htdocs/install/mysql/tables/` (see existing files for examples)
- ❌ Never run SQL queries inside loops (avoid N+1 problem — use JOINs or batch queries instead)
- ✅ Always use `LIMIT` on list queries for performance

---

## 🔌 Hooks & Extensions

- Prioritize hooks over direct code overrides
- Before creating a new hook, verify it does not already exist:
  ```
  grep -r "executeHooks" htdocs/ | grep 'hookName'
  ```
- Call hooks using the standard pattern:
  ```php
  $hookmanager->executeHooks('actionName', $parameters, $object, $action);
  ```
- Name hooks clearly and descriptively (e.g., `formObjectOptions`, `addMoreActionsButtons`)

---

## 🌍 Internationalisation

- Never hardcode user-facing strings — always use `$langs->trans('Key')`
- Language files must be placed in `mymodule/langs/en_US/` (and other locales as needed)
- Language key names must be in English and PascalCase (e.g., `MyModuleLabel`, not `MonLibellé`)
- Load the language file at the top of the page: `$langs->load('mymodule@mymodule')`

---

## 🧪 Testing & Validation

Before any modification, verify:
- Creation / edition / deletion workflows
- User rights enforcement (`$user->rights->module->action`)
- Multi-entity compatibility (`$conf->entity`)

If possible:
- Add a PHPUnit test file in `htdocs/modulebuilder/template/test/phpunit/`
- Reference the `phpunit.xml` file at the root of the project to run the test suite

---

## 🖥️ UI / UX

- Respect Dolibarr UI — no unsolicited redesigns
- Reuse existing components (buttons, forms, tables) from `htdocs/core/tpl/`
- ❌ No overly complex inline JS
- ✅ Place JavaScript in separate files under `mymodule/js/`

---

## 🔒 Security

- Always validate inputs (`GET`, `POST`) via `GETPOST()` with a type parameter
- Prevent SQL injection (use `db->escape()`) and XSS (use `dol_escape_htmltag()`)
- Always include Dolibarr CSRF tokens in POST forms: `<input type="hidden" name="token" value="'.newToken().'">`

---

## ⚡ Performance

- Avoid SQL queries inside loops (N+1 problem)
- Use JOINs or batch queries instead of multiple sequential queries
- Apply `LIMIT` and proper indexes on list queries
- Cache repeated calls to `getDolGlobalString()` or `$conf->global->` in local variables

---

## 🧾 Logs & Debug

- Use `dol_syslog()` for all logging (with appropriate log level: `LOG_DEBUG`, `LOG_WARNING`, `LOG_ERR`)
- Do not leave `var_dump()`, `print_r()`, or `die()` in committed code
- Use Dolibarr's `setEventMessages()` to display user-facing messages

---

## 🚀 Git Workflow

- Branch strategy:
    - One branch per major version (bug fixes only)
    - `develop` branch for both fixes and new features
- ❌ Never commit directly to `main` or `develop` without a reviewed PR
- Commit message format: `TYPE: #issueNumber Short description`
    - Types: `NEW`, `FIX`, `CLOSE`
    - Example: `FIX: #1234 Correct VAT calculation on credit notes`
- Update the `ChangeLog` file with a summary of significant changes
- When fixing a bug, apply the patch on the **oldest affected branch first**,
  then cherry-pick forward to newer branches and `develop`
- Do not introduce new syntax or features unavailable in the branch's minimum PHP version

---

## 🧩 What the Agent MUST Do

- Read this file before any modification
- Check if an equivalent function already exists before writing new code
- Minimize the impact of changes
- Propose modular modifications that do not affect unrelated features

---

## ❗ What the Agent MUST NOT Do

- Perform massive refactoring without an explicit request
- Change the global architecture of existing modules
- Delete code without justification and a comment explaining why
- Add external dependencies (Composer packages, JS libraries) without prior validation

---

## 💡 Key Principle

👉 Always prioritize:
**extension > modification**

---

## 📌 In Case of Doubt

- Keep it simple
- Be conservative
- Ask for confirmation before any critical or irreversible change
