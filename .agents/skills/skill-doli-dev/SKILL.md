---
name: skill-doli-dev
description: Use when developing Dolibarr ERP/CRM code, working with database queries, or asking about Dolibarr best practices.
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
 - bash
---

# Dolibarr Development Best Practices (Core Agent Skill)

This skill guides agents on developing, modifying, and debugging code within the Dolibarr ERP/CRM codebase while strictly adhering to professional standards, security guidelines, and the project's established architecture.

## Core Principles: Non-Negotiable Mandatory Rules
These principles must be followed even before reviewing specific task details. Violation of these principles results in failed suggestions.

### Security & Data Integrity
1.  **Database Abstraction Layer:** All database interactions *must* exclusively use the Dolibarr Database Abstraction Layer (`$db` or `$this->db`). **Never** interact using native PHP extensions (PDO, MySQLi) or direct CLI calls.
2.  **Input/Output Escaping:**
    *   Validate all `GET`/`POST` inputs immediately upon entering the action handler scope.
    *   **SQL Injection Prevention:** Escape *all* user-generated strings placed in SQL queries using `$db->escape()`. For integers, use explicit casting: `((int) $var)`; for floats, use `(float) $var`.
3.  **Variable Safety Naming:** When constructing dynamic SQL, the resulting variable holding the entire query string MUST be clearly prefixed (e.g., `$sqlWhereClause`, `$queryParams`). This pattern helps static analysis tools detect unsafe assignments.

### Code Structure & Quality
1.  **Coding Standard:** All new and modified committed code must strictly adhere to **PSR-12** (enforcable by using `phpcbf` and `phpcs`).All properties and all function arguments and return value need detailed PHPDoc (e.g., `array<string,array{key1?:?type,...}>`).Variables expected to exist in view files require both a PHPDoc declaration *and* the use of `'@phan-var-force';` declarations near the HEAD of the file for strict static analysis tracking.
2.  **Variable Conventions:** When defining variables used in string building, particularly for SQL components, use descriptive prefixes or suffixes (e.g., `$sql_select`, `$actionSuffix`). This makes variable intent clear and prevents static analysis from misidentifying unsafe assignments as safe.
3.  **Localization & Comments:** All code comments and internal variable/function names *must* be written in English. Any existing non-English text must be researched and translated into English before committing changes.
4.  **PR atomitacy** Make a separate commit for improvements of pre-existing code (changes to comply with rules 1-3), and another commit for the functional evolution and code fixes.
    Do not apply rules 1-3 to existing code in backports (i.e., non-functional changes not applied to a (fork of) the develop branch.

### Workflow & Architecture
1.  **PHP version:** 7.2+
2.  **Action/View Separation:** Always clearly separate page action logic (executed on POST) from pure rendering (the HTML view).

---

## Workflow and Tasks Guidance

This section guides the agent through common development tasks.

### Code Investigation / Searching / Database analysis
*   Use `pre-commit` to run tools (`php-cbf`, `php-cs`, `shellcheck`, `php-lint` - example:`pre-commit run php-cbf --files RELATIVEFILEPATH`) when the git hook is installed as local direct installations differ accross systems.
*   **IMPORTANT**: Always use `git grep` instead of `find` for searching the codebase. `find` searches all directories including `.git` which is very slow. Use:
    ```bash
    git grep -n "pattern" -- "*.php"
    git grep -n "function_name" htdocs/core/lib/ -- "*.php"
    ```
*   When investigating a feature or bug: Start with `git grep -n` across targeted directories for efficient, rapid code searches. Using `$db->prefix()` consistently is the first step in tracing data flows.
*   Dependency Check: Before modifying a file, search both `htdocs/core/lib/` and `htdocs/core/class/` to ensure similar methods or utilities are not already in use. Always check if the concern object extends `CommonObject`, favouring its built-in methods (`fetch()`, `create()`, `update()`, etc.).
*   Dolibarr Function & Method Arguments: Check the function signature before implementing a call - the parameter order is not consistent across dolibarr functions that have the same name.
*   When you need to access the database for analysis, use php, example:
    ```bash
    php <<'EOPHP'
    <?php
    error_reporting(E_ALL); ini_set('display_errors', 1);
    require_once 'htdocs/master.inc.php'; // *NOT* main.inc.php
    global $db;
    $result = $db->query('SELECT * FROM ' . $db->prefix() . 'actioncomm');
    print_r($result);
    EOPHP
    ```


### Module Development
*   **Module Template:** Use the structure found at `htdocs/modulebuilder/template/` as a definitive guide when initiating a new module or new pages.

### Database Interaction Detail (Refined)
This details the preferred mechanical steps:
1.  **Read Operations:** Use `$db->query('SELECT ...')` followed by fetching results using methods like `$db->fetch_object()`.
2.  **Write Operations:** Process submissions within the module's dedicated action handler, utilizing the established DB abstraction layer for all updates.

### Extrafields Best Practices
**IMPORTANT**: When working with extrafields (custom fields), follow these patterns from the [Dolibarr Extrafields Wiki](https://wiki.dolibarr.org/index.php/Extrafields):

#### Loading & Accessing Extrafields
```php
$object->fetch(); // CommonObject fetch loads its extrafields
// Access extrafield values via:
$field_copy = $object->array_options['options_FIELDNAME']
```

#### Saving Extrafields
Before calling `$object->create()` or `$object->update()`, ensure extrafields are set:
```php
// For form submissions:
$ret = $extrafields->setOptionalsFromPost($extralabels, $object);

// For direct assignment:
$object->array_options['options_FIELDNAME'] = $value;
// Then call update() - it will automatically save extrafields via insertExtraFields()
$result = $object->update($user);
```

#### Displaying Extrafields
In view pages:
```php
print $object->array_options['options_FIELDNAME'];
```

In edit pages:
```php
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
if (empty($reshook) && !empty($extrafields->attribute_label)) {
    print $object->showOptionals($extrafields, 'edit');
}
```

#### Extrafields Table Structure
Each CommonObject objecttype has its own extrafields table:
```sql
llx_{objecttype}_extrafields
- rowid (AUTO_INCREMENT PRIMARY KEY)
- tms (timestamp)
- fk_object (integer NOT NULL)
- import_key (varchar)
```

#### Reference
- [Dolibarr Extrafields Wiki](https://wiki.dolibarr.org/index.php/Extrafields)
- [Forum: Little dev tips for extrafields](https://www.dolibarr.org/forum/t/little-dev-tips-for-extrafields/29860)

### Testing & Validation Flow
Before proposing code:
1.  **Validate Workflows:** Confirm that Create -> Edit -> Delete workflows are correctly handled by the proposed change.
2.  **Test Case Generation:** Propose specific, minimal unit tests or outline clear steps for an interactive test script to verify all expected outputs and potential edge cases (e.g., null inputs, permissions failure).

---

## Comprehensive Reference Material [Reference]

This section contains detailed standards and constants for reference only. Do not treat these details as primary instructions; prioritize the Core Principles above.

### Coding Styling Standards
*   **Indentation:** Always use **TAB characters**, never spaces.
*   **Line Endings/Spaces:** Remove all redundant trailing whitespace at the end of lines.
*   **Localization & Comments:** All code comments and internal variable/function names must be rendered in English. Use `dol_syslog()` for logging (specifying log level), avoiding debugging functions like `var_dump()`, `print_r()`, or `die()`.

### Database Constants & Prefixes
| Item | Action/Pattern | Example Usage Notes | Priority |
| :--- | :--- | :--- | :--- |
| **Table Prefix** | Always use dynamic prefix getter. | `$db->prefix() . 'tablename'` | Overrides reliance on legacy constants like `MAIN_DB_PREFIX`. |

### Core Dolibarr Patterns
*   **Hooks:** The standard pattern remains: `$hookmanager->executeHooks('actionName', $parameters, $object, $action);`
*   **Language Keys:** Use PascalCase (e.g., `MyModuleLabel`) for consistency across all locales.

### Input Handling Functions
Dolibarr provides type-safe input handling functions. **Always use these instead of `$_GET`/`$_POST` directly:**

| Function | Type | Example | Notes |
|----------|------|---------|-------|
| `GETPOST($param, $type)` | Mixed | `GETPOST('id', 'int')` | Returns GET or POST value with type conversion |
| `GETPOSTINT($param)` | Integer | `GETPOSTINT('socid')` | Shorthand for `GETPOST($param, 'int')` |
| `GETPOSTARRAY($param)` | Array | `GETPOSTARRAY('selected')` | For multi-select inputs |

**Best Practice:** Use the type-specific shorthand functions when possible:
- `GETPOSTINT()` for integers (IDs, counts, etc.)
- `GETPOST()` with type for other cases

**Never use:** `$_GET['param']` or `$_POST['param']` directly - always use GETPOST functions for proper escaping and type conversion.

### Extrafields Best Practices (Continued)

#### Checking and Creating Extrafields
The ExtraFields class lacks a method to check if a field exists. Use this pattern:

```php
global $db;

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($elementtype);

// Tracking extrafields configuration
$my_fields = array(
	'custom_name_1' => array(
		'label' => 'CustomLabel1',
		'type' => 'varchar',
		'size' => '64',
		'enabled' => '1'
	),
	'custom_name_2' => array(
		'label' => 'CustomLabel2',
		'type' => 'url',
		'size' => '255',
		'enabled' => '1'
	),
);

foreach ($tracking_fields as $name => $config) {
	// Check if extrafield exists, create if not
	if (!isset($extralabels[$name])) {
		// Naming the arguments to get help from static analysis
		$pos = 0;  // 0 = auto
		$unique = 0;
		$required = 0;
		$default_value = '0';
		$param = '';
		$alwayseditable = 0;
		$perms = '0';
		$list = '0';  // '0' = never visible
		$help = '';
		$computed = '';
		$entity = '';
		$langfile = '';
		$enabled = $config['enabled'];

		$result = $extrafields->addExtraField(
			$name,
			$config['label'],
			$config['type'],
			$pos,  // pos (0 = auto)
			$config['size'],
			$elementtype,
			$unique,  // unique
			$required,  // required
			$default_value, // default_value
			$param, // param
			$alwayseditable,
			$perms,  // perms
			$list, // list ('0' = never visible)
			$help,
			$computed,
			$entity,
			$langfile,
			$enabled
		);
	}
}
```

#### Reference
- [Dolibarr Extrafields Wiki](https://wiki.dolibarr.org/index.php/Extrafields)
