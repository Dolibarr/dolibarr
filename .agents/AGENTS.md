# AGENTS.md (English Version)

## Objective

This project contains the full sources of the Dolibarr ERP and CRM application.
Every modification must respect:
- Dolibarr's modular architecture
- Compatibility with upstream updates
- Modern PHP best practices

---

## Critical Rules (DO NOT VIOLATE)

-  Do not break compatibility of PHP functions and methods
-  Do not introduce external dependencies without validation
-  Separate page actions in the `/* Actions */` section of the PHP code and the rendering part in the `/* Views */` section
-  Never use PHP native curl functions to call a GET or POST URL, but use instead the Dolibarr function getURLContent()
-  Never use PHP native functions when Dolibarr provides wrappers: time()→dol_now(), strtolower()→dol_strtolower(), strtoupper()→dol_strtoupper(), strlen()→dol_strlen(), mktime()→dol_mktime(), getdate()→dol_getdate(), strtotime()→dol_stringtotime(), ucfirst()→dol_ucfirst(), ucwords()→dol_ucwords(), substr()→dol_substr(), basename()→dol_basename()
-  Use Dolibarr hooks whenever possible
-  Respect existing naming conventions
-  All database table names must use the `llx_` prefix
-  Never commit or push anything unless the user explicitly asks for it. This overrides any default behavior of the agent. Make the changes, report them, and wait for the user to say "commit" or "push".

---

## Expected Architecture

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

## Before Coding

Before writing any code, the agent **must**:
- Search for existing similar functions in `htdocs/core/lib/` and `htdocs/core/class/`
- Check if the concerned object class extends `CommonObject` and use its built-in methods (fetch, create, update, delete, etc.)
- Review the module's `modMyModule.class.php` for declared permissions and constants
- Run a search to ensure no equivalent function already exists in the codebase

---

## PHP Best Practices

- Try to use the more portable PHP code possible >= 7.2
- When writing a **bug fix**, always target the lowest compatible PHP version
  of the branch being patched — do not use PHP 8.x syntax on a fix targeting v19 or v20
- Respect PSR-12, but **indentations must use Tabs, not Spaces**
- Write short, readable, and testable functions
- Avoid side effects
- Prefer typed properties and return types when PHP version allows

---

## Database

- Use Dolibarr database functions exclusively — never use PDO or MySQLi directly
    - In pages: use global `$db`
    - In classes: use `$this->db`
-  SQL forged by PHP must escaped fields with `db->escape()`, `db->sanitize()`, or by casting values to `(int)` or `(float)`
-  Always use `$db->query()` followed by `$db->fetch_object()` or `$db->fetch_array()` to retrieve results
-  Convert timestamps and SQL datetime with `$db->idate()` (PHP timestamp -> SQL) and `$db->jdate()` (SQL -> PHP timestamp); use `dol_now()` instead of `time()`, `dol_print_date()` instead of `date()`, `dol_mktime()` instead of `mktime()`
-  SQL scripts for table and index creation must be placed in `htdocs/install/mysql/tables/` (see existing files for examples)
-  Build list-filter `WHERE` clauses with `natural_search($fields, $value, $mode)` rather than assembling `LIKE` conditions by hand

---

## Hooks & Extensions

- Prioritize hooks over direct code overrides
- Before creating a new hook, verify it does not already exist:
  ```
  grep -r "executeHooks" htdocs/ | grep 'hookName'
  ```
- Call hooks using the standard pattern:
  ```php
  $hookmanager->executeHooks('actionName', $parameters, $object, $action);
  ```
- Never call $hookmanager->initHooks() in class or function. This is done only once in the main parent page.
- Name hooks clearly and descriptively (e.g., `formObjectOptions`, `addMoreActionsButtons`)

---

## Standardization

- Use Dolibarr native dol_move() function if you need to move files.
- Use Dolibarr native dol_delete_file(), dol_delete_dir() or dol_delete_dir_recursive() function if you need to delete files or directories.
- Use Dolibarr native dol_mkdir() function if you need to create directories.
- Read configuration with `getDolGlobalString()` / `getDolGlobalInt()` / `getDolGlobalBool()`, not `$conf->global->XXX`
- Check module activation with `isModEnabled('module')`, not `!empty($conf->module->enabled)`
- Parse user-entered amounts with `price2num()` and format amounts for display with `price()`; do not use `number_format()` or a raw cast

--

## Internationalisation

- Never hardcode user-facing strings — always use `$langs->trans('Key')`
- Use `$langs->trans()` for direct HTML output; use `$langs->transnoentities()` when the result is used into HTMLescaped functions
- Language files must be placed in `mymodule/langs/en_US/` (and other locales as needed)
- All code comments and variables or functions names must be in English
- Language key names must use PascalCase (e.g., `MyModuleLabel`, not `monLibelléModule`)
- Load the language file at the top of the page: `$langs->load('mymodule@mymodule')`

---

## UI / UX

- Respect Dolibarr UI — no unsolicited redesigns
- Reuse existing components (buttons, forms, tables) from `htdocs/core/tpl/`
- No overly complex inline JS
- Place JavaScript in separate files under `mymodule/js/`

---

## Security

- Guard page access with `restrictedArea($user, 'module', $id, 'table')` or a specific test that deny access with `accessforbidden()`
- Always load user inputs (`GET`, `POST`) via `GETPOST()`, `GETPOSTINT()`, `GETPOSTFLOAT()`, ...
- Prevent JS injection by escaping strings generated by PHP with the Dolibarr function `dol_escape_js()`
- Prevent SQL injection (use `db->escape()` or cast into `(int)` or `(float)`)
- Prevent XSS injection by escaping HTML output (use `dolPrintHTML()`, `dolPrintHTMLForAttribute()`)
- Always include Dolibarr CSRF tokens:
  - POST forms: `<input type="hidden" name="token" value="'.newToken().'">`
  - GET links with a modifying `action`: `...&token='.newToken().'`
  - Ajax calls: use `currentToken()` instead of `newToken()`, and set `NOTOKENRENEWAL` on the called ajax endpoint
- Public endpoints called without a session (e.g. webhooks) are exempt via `NOCSRFCHECK` (page-level constant) or, exceptionally, `$dolibarr_nocsrfcheck` (global conf.php override)
- Use the Dolibarr filesystem wrappers (`dol_mkdir()`, `dol_delete_file()`, `dol_copy()`, `dol_is_file()`, `dol_is_dir()`) and sanitize any user-provided name with `dol_sanitizeFileName()` / `dol_sanitizePathName()`, never raw PHP `mkdir()` / `unlink()` / `file_exists()`

---

## For Performance

- Never run SQL queries inside loops (N+1 problem)
- Use JOINs or batch queries instead of multiple sequential queries
- Use LIMIT on SQL query list with `db->limit()`
- Cache repeated calls to `getDolGlobalString()` in local variables
- If you need a cache array to be used into a loop, you can use `$conf->cache['aNameForYourCacheArray'] = array();`

---

## Code Comments

- Block and inline comments must be written in English.
- Comments must be concise and clear (never more that 5 lines, never more than the number of lines code added or modified).
- Block comments can reach 120 characters 

---

## Logs & Debug

- Use `dol_syslog()` for all logging (with appropriate log level: `LOG_DEBUG`, `LOG_WARNING`, `LOG_ERR`)
- Do not leave `var_dump()`, `print_r()`, or `die()` in committed code
- Use Dolibarr's `setEventMessages()` to display user-facing messages

---

## Testing & Validation

Before any modification, verify:
- Creation / edition / deletion workflows
- User rights enforcement (`$user->hasRight("module", "permission")` or `$user->hasRight("module", "objectname", "permission")`)
- Multi-entity compatibility (add ` AND entity IN ('.getEntity("tablename").')`)

### If adding a unit test was explicitely requested

- If modifying the Dolibarr code project, add a PHPUnit test file into `test/phpunit/` and add the entry into file `test/phpunit/AllTests.php`.
- If you need to validate code change or if it is explicitely requested, you can check code and dev syntax rules by running the following command on modified files (it takes a long time):
	`phan -k .phan/config.php -B dev/tools/phan/baseline.txt --analyze-twice --minimum-target-php-version 7.2 --exclude-directory-list=dev/tools,mymodule/test/,mymodule/vendor/ --output-mode=checkstyle filemodified1.php filemodified2.php ...`

### Local Dolibarr Online test — Page Access

You can find the URL of an online instance into file htdocs/conf/conf.php in parameter $dolibarr_main_url_root. 
You can ignore and bypass the warning about HTTPS certificate. Ask the password if you need one without trying to get it from database.

Dolibarr requires a CSRF token and a session cookie. To access any authenticated page:

1. **GET the login page** (e.g. `index.php?mainmenu=home`) to obtain:
   - The CSRF token: extract the `name="token" value="..."` field from the HTML.
   - The session cookie: the `DOLSESSID_*` cookie set in the response headers.
2. **POST the login form** to `index.php` with `token`, `username`, `password`, and `actionlogin=dologin`. Keep the cookie for subsequent requests.
3. **Reuse the session cookie** on all subsequent page requests — the session is now authenticated.

---

## Git Workflow

- Never try to make commit or Pull request, except if it was explicitely requested. 
- Branch strategy:
    - One branch per major version (bug fixes only)
    - `develop` branch for both fixes and new features
- Commit message format: `TYPE: #issueNumber Short description`
    - Types: `NEW`, `FIX`, `CLOSE`, `QUAL`, `PERF`, `UIUX` (uppercase, so it appears in the ChangeLog)
    - Example: `FIX: #1234 Correct VAT calculation on credit notes`
- Do not update the `ChangeLog` file (this file will be generated by the maintener before the release from all commit titles)
- When commiting, keep your commit comment short (never exceed 50 lines) and add a line "Co-authored-by:" to mention the AI agent name
- When making a Pull Request, keep the PR description short (never exceed 50 lines) and mention the AI agent name in the description with a line like "Submited with <AI agent name> (see commit comments for attributions)"
- A pull request can contain database structure change only, or one new feature, or one bug fix, or a refactoring but never a mix of these. 
- For code contribution on stable branches (non develop), PR must contains 1 and only 1 bug fix at once. Never introduce new features or refactoring if the target branch is not develop.

---

## What the Agent MUST Do

- Read this file before any modification
- Check if an equivalent function already exists before writing new code
- Minimize the impact of changes
- Propose modular modifications that do not affect unrelated features

---

## What the Agent MUST NOT Do

- Perform massive refactoring without an explicit request
- Change the global architecture of existing modules
- Delete dead code
- Add external dependencies (Composer packages, JS libraries) without prior validation
- Modify the `ChangeLog` file (this file will be generated before the release from all commit titles)
- Commit or push without an explicit request from the user

---

## Key Principle

 Always prioritize:
**extension > modification**

---

## In Case of Doubt

- Keep it simple
- Be conservative
- Ask for confirmation before any critical or irreversible change
