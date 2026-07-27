# Subtotals Predefined Phrases Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin define a dictionary of predefined phrases (Setup > Dictionaries) that users can pick from a dropdown to fill the description of a "title" line in the `subtotals` module, on top of the existing free-text entry.

**Architecture:** A new dictionary table `llx_c_subtotals_phrases` is created and loaded through the module's own `init()` (like `recruitment`/`bom`), registered in `modSubtotals::$dictionaries` so the generic Setup > Dictionaries screen manages it with no dedicated admin page. A new `CommonSubtotal::getPredefinedPhrases()` trait method reads the active rows. `subtotal_create.tpl.php` and `subtotal_edit.tpl.php` each gain an optional `<select>` whose `onchange` copies the chosen label into the existing free-text description field — a pure client-side convenience, no change to how the line is actually saved.

**Tech Stack:** PHP 8.5 (Docker php-fpm), MariaDB, Dolibarr module/dictionary framework, jQuery (already loaded on these pages).

## Global Constraints

- Indentation: tabs, not spaces (PSR-12 otherwise) — spec: repo-wide PHP style rule.
- Do not add `declare(strict_types=1)` — these are edits to existing core files, not new external modules.
- DB access only through `$this->db` / global `$db`; escape with `$db->escape()`/`(int)` casts; never raw `$_GET`/`$_POST` (use `GETPOST()`).
- Table prefix `llx_`; new dictionary table name `c_subtotals_phrases`.
- No SQL inside loops.
- Commit message format: `TYPE: Short description` (`NEW` for this feature; no tracked issue number for this change, matching current repo history where many commits omit the `#issueNumber` when there is no tracked issue).
- Do not commit to `develop` directly — work happens on the existing branch `subtotals-predefined-phrases` (already created and checked out, holds the approved design spec commit).
- Never use `--no-verify`; if the pre-commit hook (PHPStan/Phan/lang-key checks) fails, fix the real issue and commit again.

Reference design doc: `docs/superpowers/specs/2026-07-27-subtotals-predefined-phrases-design.md`

Local verification environment (already running): Docker stack (`docker compose ps` shows `mariadb`, `php-fpm`, `webserver` up). DB reachable at `127.0.0.1:3306`, database `dolibarr`, user `dolibarr_user`, password `dolibarr_password` (see `docker/php-fpm/conf.php` / `docker-compose.yml` — this differs from the generic non-Docker credentials mentioned elsewhere). The `subtotals` module is currently enabled (`llx_const` has `MAIN_MODULE_SUBTOTALS`).

---

### Task 1: Dictionary table SQL files

**Files:**
- Create: `htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.sql`
- Create: `htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.key.sql`
- Create: `htdocs/install/mysql/data/llx_c_subtotals_phrases-subtotals.sql`

**Interfaces:**
- Produces: table `llx_c_subtotals_phrases(rowid, entity, code, label, active)` with a unique index on `(entity, code)`, and 4 seed rows. Task 2 loads these files automatically via `DolibarrModules::_load_tables('/install/mysql/', 'subtotals')` (files must contain `-subtotals.` in their name to be picked up — already satisfied by the names above).

- [ ] **Step 1: Create the table definition file**

`htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.sql`:

```sql
-- ========================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- Dictionary of predefined phrases usable as description of a subtotal title line
-- ========================================================================

create table llx_c_subtotals_phrases
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  entity      integer DEFAULT 1 NOT NULL,
  code        varchar(32),
  label       varchar(255),
  active      tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;
```

- [ ] **Step 2: Create the unique-index file**

`htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.key.sql`:

```sql
-- ========================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
-- ========================================================================

ALTER TABLE llx_c_subtotals_phrases ADD UNIQUE INDEX uk_c_subtotals_phrases_code_entity (entity, code);
```

- [ ] **Step 3: Create the seed data file**

`htdocs/install/mysql/data/llx_c_subtotals_phrases-subtotals.sql`:

```sql
-- ========================================================================
-- Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
-- ========================================================================

INSERT INTO llx_c_subtotals_phrases (entity, code, label, active) VALUES (__ENTITY__, 'OPTIONS', 'Options', 1);
INSERT INTO llx_c_subtotals_phrases (entity, code, label, active) VALUES (__ENTITY__, 'PRESTATIONS', 'Prestations', 1);
INSERT INTO llx_c_subtotals_phrases (entity, code, label, active) VALUES (__ENTITY__, 'FOURNITURES', 'Fournitures', 1);
INSERT INTO llx_c_subtotals_phrases (entity, code, label, active) VALUES (__ENTITY__, 'MAINDOEUVRE', 'Main d''oeuvre', 1);
```

- [ ] **Step 4: Verify the SQL is syntactically valid against the dev database**

The `__ENTITY__` placeholder is only substituted by Dolibarr's installer, so replace it with `1` for this manual dry run. Run from the repo root:

```bash
sed 's/__ENTITY__/1/g' dolibarr/htdocs/install/mysql/data/llx_c_subtotals_phrases-subtotals.sql \
  > /tmp/seed_check.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < dolibarr/htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < dolibarr/htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.key.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < /tmp/seed_check.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "SELECT rowid, entity, code, label, active FROM llx_c_subtotals_phrases ORDER BY rowid;"
```

Expected: no SQL errors, and the final `SELECT` prints 4 rows (`OPTIONS`/Options, `PRESTATIONS`/Prestations, `FOURNITURES`/Fournitures, `MAINDOEUVRE`/Main d'oeuvre).

- [ ] **Step 5: Drop the table again**

This dry run only proves the SQL is valid — the real, repeatable load path is `DolibarrModules::_load_tables()`, wired up in Task 2. Drop the table so Task 2 exercises that path from a clean state:

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DROP TABLE llx_c_subtotals_phrases;"
```

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.sql \
        htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.key.sql \
        htdocs/install/mysql/data/llx_c_subtotals_phrases-subtotals.sql
git commit -m "NEW: Add llx_c_subtotals_phrases dictionary table"
```

---

### Task 2: Wire the dictionary into modSubtotals

**Files:**
- Modify: `htdocs/core/modules/modSubtotals.class.php`

**Interfaces:**
- Consumes: Task 1's SQL files (loaded via `_load_tables('/install/mysql/', 'subtotals')`, inherited from `DolibarrModules`).
- Produces: on module init, table `llx_c_subtotals_phrases` exists and is populated; the dictionary "SubtotalsPredefinedPhrases" is listed and editable under Setup > Dictionaries. Task 3's `getPredefinedPhrases()` reads from this table.

- [ ] **Step 1: Add the dictionaries declaration to the constructor**

In `htdocs/core/modules/modSubtotals.class.php`, the constructor currently has (around line 93-96):

```php
		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
```

Insert a new "Dictionaries" block between the `$this->const = array();` line and the `// Array to add new pages in new tabs` comment:

```php
		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'subtotals',
			'tabname' => array("c_subtotals_phrases"),
			'tablib' => array("SubtotalsPredefinedPhrases"),
			'tabsql' => array('SELECT rowid, code, label, active, entity FROM '.MAIN_DB_PREFIX.'c_subtotals_phrases'),
			'tabsqlsort' => array("label ASC"),
			'tabfield' => array("code,label"),
			'tabfieldvalue' => array("code,label"),
			'tabfieldinsert' => array("code,label,entity"),
			'tabrowid' => array("rowid"),
			'tabcond' => array(isModEnabled('subtotals')),
			'tabhelp' => array(array()),
		);

		// Array to add new pages in new tabs
```

- [ ] **Step 2: Load the module's own SQL files on init**

In the same file, the `init()` method currently reads (around line 123-134):

```php
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$sql = array(
			//	"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'holiday' AND entity = ".((int) $conf->entity),
			//	"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','holiday',".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
```

Change it to load the tables/data files created in Task 1 before running the rest of `init()`:

```php
	public function init($options = '')
	{
		$result = $this->_load_tables('/install/mysql/', 'subtotals');
		if ($result < 0) {
			return -1; // Do not activate module if error occurred while loading module SQL queries
		}

		// Permissions
		$this->remove($options);

		$sql = array(
			//	"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'holiday' AND entity = ".((int) $conf->entity),
			//	"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','holiday',".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
```

- [ ] **Step 3: Run PHPStan on the modules directory**

```bash
cd dolibarr
php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G htdocs/core/modules/modSubtotals.class.php
```

Expected: no new errors introduced by this file (pre-existing baseline errors elsewhere are fine).

- [ ] **Step 4: Verify by re-running module init through a throwaway CLI script**

Create a temporary file `dolibarr/scripts/tmp_verify_subtotals_init.php` (NOT part of the commit — delete it after this step) with:

```php
<?php
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}

require __DIR__ . '/../htdocs/main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/modules/modSubtotals.class.php';

/** @var DoliDB $db */
$mod = new modSubtotals($db);
$result = $mod->init('');

echo "init() result: " . var_export($result, true) . "\n";
echo "Errors: " . implode(', ', $mod->errors ?? []) . "\n";
```

Run it and inspect the database:

```bash
docker compose exec -T php-fpm php /application/dolibarr/scripts/tmp_verify_subtotals_init.php

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "SHOW TABLES LIKE 'llx_c_subtotals_phrases'; SELECT rowid, code, label, active FROM llx_c_subtotals_phrases ORDER BY rowid;"
```

Expected: `init() result: 1`, no errors printed, `llx_c_subtotals_phrases` exists with the 4 seeded rows.

Then delete the throwaway script:

```bash
rm dolibarr/scripts/tmp_verify_subtotals_init.php
```

- [ ] **Step 5: Verify the dictionary shows up in Setup > Dictionaries**

Open `https://localhost/admin/dict.php` in a browser (logged in as an admin), and confirm a "Subtotals predefined phrases" entry appears in the dictionary list (bottom section, dictionaries contributed by modules) with the 4 seeded rows editable.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/core/modules/modSubtotals.class.php
git commit -m "NEW: Register subtotals predefined phrases dictionary and load its tables on module init"
```

---

### Task 3: `CommonSubtotal::getPredefinedPhrases()`

**Files:**
- Modify: `htdocs/subtotals/class/commonsubtotal.class.php`

**Interfaces:**
- Consumes: table `llx_c_subtotals_phrases` (Task 1/2), `$this->db` (available on every class using the `CommonSubtotal` trait: `Facture`, `FactureRec`, `Propal`, `Commande`, `Expedition`, `SupplierProposal`, `CommandeFournisseur`, `FactureFournisseur`, `Fichinter`).
- Produces: `public function getPredefinedPhrases(): array<string,string>` — keys and values are both the phrase label, sorted alphabetically, only `active = 1` rows for the current entity. Used by Task 5 (`subtotal_create.tpl.php`) and Task 6 (`subtotal_edit.tpl.php`).

- [ ] **Step 1: Add the method to the trait**

In `htdocs/subtotals/class/commonsubtotal.class.php`, the `getPossibleLevels()` method ends at line 845 (`return $depth_array;` then closing `}`), immediately followed by the docblock for `getDisabledShippmentSubtotalLines()`. Insert the new method between them:

```php
	/**
	 * Retrieve the current object possible levels (defined in admin page)
	 *
	 * @param Translate $langs 		Translations.
	 * @return array<int,string>	The set of possible levels, empty if not defined correctly.
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getPossibleLevels($langs)
	{
		$depth_array = array();
		$max_depth = getDolGlobalString('SUBTOTAL_'.strtoupper($this->element).'_MAX_DEPTH', 2);
		for ($i = 0; $i < $max_depth; $i++) {
			$depth_array[$i + 1] = $langs->trans("SubtotalLevel", $i + 1);
		}
		return $depth_array;
	}

	/**
	 * Retrieve the list of active predefined phrases usable as description of a title line.
	 *
	 * @return array<string,string>	Array with the phrase label as both key and value, sorted alphabetically
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getPredefinedPhrases()
	{
		$phrases = array();

		$sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_subtotals_phrases";
		$sql .= " WHERE active = 1 AND entity IN (".getEntity('c_subtotals_phrases').")";
		$sql .= " ORDER BY label ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$phrases[$obj->label] = $obj->label;
			}
		}

		return $phrases;
	}

	/**
	 * Returns an array with the IDs of the line that we don't need to show to avoid empty blocks
	 *
	 * @return array<int>	$total_ht
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getDisabledShippmentSubtotalLines()
```

(Only the new method plus its neighbors are shown for placement; the body of `getDisabledShippmentSubtotalLines()` itself is unchanged.)

- [ ] **Step 2: Run PHPStan on the subtotals class directory**

```bash
cd dolibarr
php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G htdocs/subtotals/class
```

Expected: no new errors.

- [ ] **Step 3: Verify with a throwaway CLI script**

Create a temporary file `dolibarr/scripts/tmp_verify_getpredefinedphrases.php` (NOT part of the commit — delete it after this step):

```php
<?php
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}

require __DIR__ . '/../htdocs/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

/** @var DoliDB $db */
$object = new Propal($db);
$phrases = $object->getPredefinedPhrases();

echo "Predefined phrases:\n";
print_r($phrases);
```

Run it:

```bash
docker compose exec -T php-fpm php /application/dolibarr/scripts/tmp_verify_getpredefinedphrases.php
```

Expected output: an array of 4 entries, keys and values both equal to `Options`, `Prestations`, `Fournitures`, `Main d'oeuvre` (alphabetical order: `Fournitures`, `Main d'oeuvre`, `Options`, `Prestations`).

Then delete the throwaway script:

```bash
rm dolibarr/scripts/tmp_verify_getpredefinedphrases.php
```

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/subtotals/class/commonsubtotal.class.php
git commit -m "NEW: Add CommonSubtotal::getPredefinedPhrases()"
```

---

### Task 4: Language keys

**Files:**
- Modify: `htdocs/langs/en_US/subtotals.lang`
- Modify: `htdocs/langs/fr_FR/subtotals.lang`

**Interfaces:**
- Produces: translation keys `SubtotalsPredefinedPhrases` (dictionary tab label, used by Task 2's `tablib`) and `PredefinedPhrase` (select field label, used by Task 5 and Task 6).

- [ ] **Step 1: Add keys to `htdocs/langs/en_US/subtotals.lang`**

Current file has this `# Admin` section:

```
#
# Admin
#
SubtotalSetup=Subtotals module setup
MaxSubtotalLevel=Maximum level
SubtotalLineBackColor=Level %s lines background color
NotSupportedByAllPDF=Will work with newer PDF but not with old ones %s
```

Change to:

```
#
# Admin
#
SubtotalSetup=Subtotals module setup
MaxSubtotalLevel=Maximum level
SubtotalLineBackColor=Level %s lines background color
NotSupportedByAllPDF=Will work with newer PDF but not with old ones %s
SubtotalsPredefinedPhrases=Subtotals predefined phrases
```

And the `# Card` section currently starts:

```
#
# Card
#
AddTitleLine=Add title line
AddSubtotalLine=Add subtotal line
```

Change to:

```
#
# Card
#
AddTitleLine=Add title line
AddSubtotalLine=Add subtotal line
PredefinedPhrase=Predefined phrase
```

- [ ] **Step 2: Add keys to `htdocs/langs/fr_FR/subtotals.lang`**

Current `# Admin` section:

```
#
# Admin
#
SubtotalSetup=Configuration du module Sous-totaux
MaxSubtotalLevel=Niveau maximum
SubtotalLineBackColor=Niveau %s lignes couleur d'arrière-plan
NotSupportedByAllPDF=Fonctionnera avec les PDF plus récents mais pas avec les anciens %s
```

Change to:

```
#
# Admin
#
SubtotalSetup=Configuration du module Sous-totaux
MaxSubtotalLevel=Niveau maximum
SubtotalLineBackColor=Niveau %s lignes couleur d'arrière-plan
NotSupportedByAllPDF=Fonctionnera avec les PDF plus récents mais pas avec les anciens %s
SubtotalsPredefinedPhrases=Phrases prédéfinies de sous-totaux
```

Current `# Card` section:

```
#
# Card
#
AddTitleLine=Ajouter une ligne de titre
AddSubtotalLine=Ajouter une ligne de sous-total
```

Change to:

```
#
# Card
#
AddTitleLine=Ajouter une ligne de titre
AddSubtotalLine=Ajouter une ligne de sous-total
PredefinedPhrase=Phrase prédéfinie
```

- [ ] **Step 3: Verify the files parse with no duplicate/malformed keys**

```bash
cd dolibarr
grep -c "^SubtotalsPredefinedPhrases=" htdocs/langs/en_US/subtotals.lang htdocs/langs/fr_FR/subtotals.lang
grep -c "^PredefinedPhrase=" htdocs/langs/en_US/subtotals.lang htdocs/langs/fr_FR/subtotals.lang
```

Expected: `1` for every line (each key appears exactly once per file).

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/langs/en_US/subtotals.lang htdocs/langs/fr_FR/subtotals.lang
git commit -m "NEW: Add language keys for subtotals predefined phrases"
```

(The repo's pre-commit hook runs a language-key checker — if it reports anything for these two files, fix it before proceeding; it passing is part of this step's success criteria.)

---

### Task 5: Predefined-phrase dropdown on title line creation

**Files:**
- Modify: `htdocs/core/tpl/subtotal_create.tpl.php`

**Interfaces:**
- Consumes: `$this->getPredefinedPhrases()` (Task 3), `PredefinedPhrase` lang key (Task 4).
- Produces: no new interface for other tasks — this is a leaf UI change.

- [ ] **Step 1: Edit the `title` branch of the formquestion builder**

Current code (full `title`/`subtotal` block):

```php
if ($type == 'title') {
	$formquestion = array(
		array('type' => 'text', 'name' => 'subtotallinedesc', 'label' => $langs->trans("SubtotalLineDesc"), 'moreattr' => 'placeholder="' . $langs->trans("Description") . '"'),
		array('type' => 'select', 'name' => 'subtotallinelevel', 'label' => $langs->trans("SubtotalLineLevel"), 'values' => $depth_array, 'default' => 1, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowuponpdf', 'label' => $langs->trans("ShowUPOnPDF")),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleforcepagebreak', 'label' => $langs->trans("ForcePageBreak")),
	);
} elseif ($type == 'subtotal') {
```

Replace with:

```php
if ($type == 'title') {
	$formquestion = array();

	$predefinedphrases = $this->getPredefinedPhrases();	// @phan-suppress-current-line PhanUndeclaredMethod
	if (!empty($predefinedphrases)) {
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedphrase',
			'label' => $langs->trans("PredefinedPhrase"),
			'values' => $predefinedphrases,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="if (jQuery(this).val()) { jQuery(\'#subtotallinedesc\').val(jQuery(this).val()); }"',
		);
	}

	$formquestion = array_merge($formquestion, array(
		array('type' => 'text', 'name' => 'subtotallinedesc', 'label' => $langs->trans("SubtotalLineDesc"), 'moreattr' => 'placeholder="' . $langs->trans("Description") . '"'),
		array('type' => 'select', 'name' => 'subtotallinelevel', 'label' => $langs->trans("SubtotalLineLevel"), 'values' => $depth_array, 'default' => 1, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowuponpdf', 'label' => $langs->trans("ShowUPOnPDF")),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleforcepagebreak', 'label' => $langs->trans("ForcePageBreak")),
	));
} elseif ($type == 'subtotal') {
```

`subtotalpredefinedphrase` is never read server-side: it is purely a client-side helper that writes into `#subtotallinedesc`, which is the field actually submitted and processed by `CommonSubtotal::addSubtotalLine()`.

- [ ] **Step 2: Run PHP lint on the file**

```bash
cd dolibarr
php -l htdocs/core/tpl/subtotal_create.tpl.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual browser verification**

1. Open `https://localhost` and log in as an admin.
2. Open (or create) a draft quote (Propal) that has at least one product/service line.
3. Use the "Add title line" action.
4. Confirm a "Predefined phrase" dropdown appears above the description field, listing `Fournitures`, `Main d'oeuvre`, `Options`, `Prestations`.
5. Select "Options" and confirm the description field is immediately filled with `Options`.
6. Edit the description field to append extra text (e.g. `Options avancées`) and submit — confirm the created title line's description is exactly what was in the text field (`Options avancées`), proving the dropdown is only a convenience and free text still works.
7. Repeat without touching the dropdown at all (pure free text) — confirm it still works exactly as before this change.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/core/tpl/subtotal_create.tpl.php
git commit -m "NEW: Add predefined phrase dropdown to title line creation form"
```

---

### Task 6: Predefined-phrase dropdown on title line inline edit

**Files:**
- Modify: `htdocs/core/tpl/subtotal_edit.tpl.php`

**Interfaces:**
- Consumes: `$this->getPredefinedPhrases()` (Task 3), `PredefinedPhrase` lang key (Task 4), `Form::selectarray()` (existing core method, already used elsewhere in this file for `line_depth`).
- Produces: no new interface for other tasks — leaf UI change.

- [ ] **Step 1: Edit the description-input block**

Current code:

```php
	if (!$situationinvoicelinewithparent) {
		print '<input type="text" name="line_desc" class="marginrightonly" id="line_desc" value="';
		print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description . '"';
		$disabled = 0;
		if ($line_type == 'subtotal') {
			print ' readonly="readonly"';
			$disabled = 1;
		}
		print '>';
		$depth_array = $this->getPossibleLevels($langs);  // Suppose CommonSubtotal trait @phan-suppress-current-line PhanUndeclaredMethod
		print $form->selectarray('line_depth', $depth_array, abs($line->qty), 0, 0, 0, '', 0, 0, $disabled);
```

Replace with:

```php
	if (!$situationinvoicelinewithparent) {
		print '<input type="text" name="line_desc" class="marginrightonly" id="line_desc" value="';
		print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description . '"';
		$disabled = 0;
		if ($line_type == 'subtotal') {
			print ' readonly="readonly"';
			$disabled = 1;
		}
		print '>';
		if ($line_type == 'title') {
			$predefinedphrases = $this->getPredefinedPhrases();  // @phan-suppress-current-line PhanUndeclaredMethod
			if (!empty($predefinedphrases)) {
				print $form->selectarray('line_predefinedphrase', $predefinedphrases, '', 1, 0, 0, 'onchange="if (jQuery(this).val()) { jQuery(\'#line_desc\').val(jQuery(this).val()); }"', 0, 0, 0, '', 'minwidth100');
			}
		}
		$depth_array = $this->getPossibleLevels($langs);  // Suppose CommonSubtotal trait @phan-suppress-current-line PhanUndeclaredMethod
		print $form->selectarray('line_depth', $depth_array, abs($line->qty), 0, 0, 0, '', 0, 0, $disabled);
```

`line_predefinedphrase` is never read server-side, same reasoning as Task 5: `line_desc` remains the field that is actually submitted and processed by `CommonSubtotal::updateSubtotalLine()`.

- [ ] **Step 2: Run PHP lint on the file**

```bash
cd dolibarr
php -l htdocs/core/tpl/subtotal_edit.tpl.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual browser verification**

1. On the same draft quote used in Task 5, click "Edit" on an existing title line.
2. Confirm the "Predefined phrase" dropdown appears next to the description input.
3. Confirm it is absent when editing a subtotal line (its description input stays read-only, unchanged from before this change).
4. Select "Prestations" from the dropdown and confirm the description input updates to `Prestations`.
5. Save and confirm the title line's description is updated correctly.
6. Confirm editing a title line purely via free text (ignoring the dropdown) still works exactly as before.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/core/tpl/subtotal_edit.tpl.php
git commit -m "NEW: Add predefined phrase dropdown to title line inline edit form"
```

---

## After all tasks

Update `ChangeLog` with a line describing this feature (per repo convention for significant changes), then consider whether to open a PR from `subtotals-predefined-phrases` onto `develop`.
