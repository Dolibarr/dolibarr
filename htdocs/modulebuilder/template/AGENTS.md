# AGENTS.md (English Version)

## Objective

This project contains the sources of a module of the Dolibarr ERP and CRM application.
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
-  Use Dolibarr hooks whenever possible
-  Never rewrite what Dolibarr already provides: call the core function, method or constant instead of coding your own. Look, in this order, at the object the caller already loaded (its properties and constants), at the methods of its class, then at `htdocs/core/lib/`. A module-side copy of a core behaviour is a bug, even when it looks shorter than the call
-  Respect existing naming conventions
-  All database table names must use the `llx_` prefix
-  Never commit or push anything unless the user explicitly asks for it. This overrides any default behavior of the agent. Make the changes, report them, and wait for the user to say "commit" or "push".
-  Never commit or push phpunit test unless the user explicitly asks for it. This overrides any default behavior of the agent. Make the changes, report them, and wait for the user to say "include the phpunit" or "discard the phpunit"

---

## Expected Architecture

External module structure:
`htdocs/mymodule`
├── `admin/`
├── `class/`
├── `core/`
├── `css/`
├── `doc/`
├── `js/`
├── `langs/`
├── `lib/`
├── `sql/`
├── `test/`
└── `tpl/`

Do not explore other directories than the workdir (that contains external modules) and the directory of Dolibarr project (that is in is ~/git/dolibarr). 
A template of an external module directory content can be found in the `htdocs/modulebuilder/template` folder of the Dolibarr project.

---

## Before Coding

Before writing any code, the agent **must**:
- Search for existing similar functions in `htdocs/core/lib/` and `htdocs/core/class/`
- Check if the concerned object class extends `CommonObject` and use its built-in methods (fetch, create, update, delete, etc.)
- Review the module's `modMyModule.class.php` for declared permissions and constants
- Run a search to ensure no equivalent function already exists in the codebase

---

## PHP Best Practices

- When writing a **bug fix**, target the lowest compatible PHP version of the module (see `modMyModule.class.php` for the `phpmin` property).
- Respect PSR-12, but **indentations must use Tabs, not Spaces**
- Write short, readable, and testable functions
- Avoid side effects
- Prefer typed properties and return types when PHP version allows

---

## Database

- Use Dolibarr database functions exclusively — never use PDO or MySQLi directly
    - In pages: use global `$db`
    - In classes: use `$this->db`
- SQL forged by PHP must escaped fields with `db->escape()`, `db->sanitize()`, or by casting values to `(int)` or `(float)`
- Always use `db->query()` followed by `db->fetch_object()` or `db->fetch_array()` to retrieve results
- SQL scripts for table and index creation must be placed in `htdocs/install/mysql/tables/` (see existing files for examples)

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
- Name hooks clearly and descriptively (e.g., `formObjectOptions`, `addMoreActionsButtons`)

---

## Internationalisation

- Never hardcode user-facing strings — always use `$langs->trans('Key')`
- Language files must be placed in `mymodule/langs/en_US/` (and other locales as needed)
- All code comments and variables or functions names must be in English.
- Language key names must use PascalCase (e.g., `MyModuleLabel`, not `monLibelléModule`)
- Load the language file at the top of the page: `$langs->load('mymodule@mymodule')`

---

## Standardization

- Use Dolibarr native dol_move() function if you need to move files.
- Use Dolibarr native dol_delete_file(), dol_delete_dir() or dol_delete_dir_recursive() function if you need to delete files or directories.
- Use Dolibarr native dol_mkdir() function if you need to create directories.
- Read the state of an object from the object itself (`$object->status` compared to `FactureFournisseur::STATUS_DRAFT`, ...), not from a new query on its table
- Read configuration with `getDolGlobalString()` / `getDolGlobalInt()` / `getDolGlobalBool()`, not `$conf->global->XXX`
- Check module activation with `isModEnabled('module')`, not `!empty($conf->module->enabled)`

---

## UI / UX

- Respect Dolibarr UI — no unsolicited redesigns
- Reuse existing components (buttons, forms, tables) from `htdocs/core/tpl/`
- No overly complex inline JS
- Place JavaScript in separate files under `mymodule/js/`

---

## Security

- Always validate user inputs (`GET`, `POST`) via `GETPOST()` with a type parameter
- Prevent SQL injection (use `db->escape()` or cast into `(int)` or `(float)`)
- Prevent XSS injection by escaping HTML output (use `dolPrintHTML()`, `dolPrintHTMLForAttribute()`)
- Always include Dolibarr CSRF tokens in POST forms: `<input type="hidden" name="token" value="'.newToken().'">`

---

## Performance

- Avoid SQL queries inside loops (N+1 problem)
- Use JOINs or batch queries instead of multiple sequential queries
- Apply `LIMIT` and proper indexes on list queries
- Cache repeated calls to `getDolGlobalString()` or `$conf->global->` in local variables

---

## Logs & Debug

- Use `dol_syslog()` for all logging (with appropriate log level: `LOG_DEBUG`, `LOG_WARNING`, `LOG_ERR`)
- Do not leave `var_dump()`, `print_r()`, or `die()` in committed code
- Use Dolibarr's `setEventMessages()` to display user-facing messages

---

## Git Workflow

- Branch strategy:
    - One branch per major version (bug fixes only)
    - `develop` branch for both fixes and new features
- Never commit directly to `main` or `develop` or any branch name matching regex `^\d+\.\d+$` but use a Pull Request.
- Commit message format: `TYPE: #issueNumber Short description`
    - Types: `NEW`, `FIX` or `CLOSE`
    - Example: `FIX: #1234 Correct VAT calculation on credit notes`
- Do not update the `ChangeLog` file (this file will be generated before the release from all commit titles)
- Do not introduce new syntax or features unavailable in the branch's minimum PHP version
- When committing, mention the AI agent name in the commit message (e.g. "Co-authored-by: AI Agent <ai-agent@dolibarr.org>")

---

## Security

- Always validate user inputs (`GET`, `POST`) via `GETPOST()` with a type parameter
- Prevent SQL injection (use `db->escape()` or cast into `(int)` or `(float)`)
- Prevent XSS injection by escaping HTML output (use `dolPrintHTML()`, `dolPrintHTMLForAttribute()`)
- Always include Dolibarr CSRF tokens in POST forms: `<input type="hidden" name="token" value="'.newToken().'">`

---

## Performance

- Never run SQL queries inside loops (N+1 problem)
- Use JOINs or batch queries instead of multiple sequential queries
- Use LIMIT on SQL query list with `db->limit()`
- Cache repeated calls to `getDolGlobalString()` in local variables
- If you need a cache array to be used into a loop, you can use `$conf->cache['aNameForYourCacheArray'] = array();`

---

## Logs & Debug

- Use `dol_syslog()` for all logging (with appropriate log level: `LOG_DEBUG`, `LOG_WARNING`, `LOG_ERR`)
- Do not leave `var_dump()`, `print_r()`, or `die()` in committed code
- Use Dolibarr's `setEventMessages()` to display user-facing messages

---

## Comments

- Block and inline comments must be written in English.
- Comments must be concise and clear (never more that 5 lines, never more than the number of lines code added or modified).
- Block comments can reach 120 characters 

---

## Testing & Validation

Before any modification, verify:
- Creation / edition / deletion workflows
- User rights enforcement (`$user->hasRights("module", "permission")` or `$user->hasRights("module", "objectname", "permission")`)
- Multi-entity compatibility (add ` AND entity IN ('.getDolEntity("tablename").')` in SQL requests)

### If adding a unit test was explicitly requested

- If making or modifying external module, add PHPUnit test files in `yourmoduledir/test/phpunit/`.
- **One test file per source file under test**: a new case goes into the test file of the class or library file it exercises, as a new method. Create a file only when that source file has no test file yet, and split by direction (export / import) rather than by issue when a file grows past about a thousand lines. The CI reads what a test file loads with `dol_include_once()` and refuses a new file whose source already has one.
- If you need to validate code change or if it is explicitly requested, you can check code and dev syntax rules by running the following command on modified files (it takes a long time):
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

- Branch strategy:
    - One branch per major version (bug fixes only)
    - `develop` branch for both fixes and new features
- Never commit directly to `main` or `develop` or any branch name matching regex `^\d+\.\d+$` but use a Pull Request.
- Commit message format: `TYPE: #issueNumber Short description`
    - Types: `NEW`, `FIX` or `CLOSE`
    - Example: `FIX: #1234 Correct VAT calculation on credit notes`
- Do not update the `ChangeLog` file (this file will be generated by the maintener before the release from all commit titles)
- When committing, keep your commit comment short (never exceed 50 lines) and add a line "Co-authored-by:" to mention the AI agent name
- When making a Pull Request, keep the PR description short (never exceed 80 lines) and mention the AI agent name in the description with a line like `Submitted with <AI agent name> (see commit comments for attributions)`
- When fixing a security vulnerability, start PR title with `SEC:` and if you know the name of the vulnerability reporter or a tracking number, mention them in the PR title.
- A pull request can contain database structure change only, or one new feature, or one bug fix, or a refactoring but never a mix of these. 
- For code contribution on stable branches (non develop), PR must contains 1 and only 1 bug fix at once. Never introduce new features or refactoring if the target branch is not develop.

---

## What the Agent MUST Do

- Before starting, load the skill `skill-doli-devmodule`
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
- Modify the `ChangeLog` file (this file is generated by the maintainer during the release process)

---

## In Case of Doubt

- Keep it simple
- Be conservative
- Ask for confirmation before any critical or irreversible change
