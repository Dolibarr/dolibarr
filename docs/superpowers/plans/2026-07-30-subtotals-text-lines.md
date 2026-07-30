# Subtotals Free-Text Lines Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a third `subtotals`-module line kind — a free-text line (`qty = 0`, no level, no closing pair) — with its own multi-line predefined-text dictionary, available on all 9 document types the module already supports.

**Architecture:** Text lines reuse the existing `special_code = SUBTOTALS_SPECIAL_CODE` / `product_type = 9` marking with `qty = 0` as the discriminator. Investigation while writing this plan (see "Corrections to the design spec" below) found that `CommonSubtotal::addSubtotalLine()` / `updateSubtotalLine()` already handle `depth == 0` correctly with **zero code changes** — every `if ($depth > 0)` / `if ($depth < 0)` guard in those methods simply falls through for `depth == 0`, landing on the generic per-module `addline()`/`updateline()` call. The only genuinely new trait code is a new dictionary-reading method. Per-document-type work is: one new UI branch in each of 3 shared templates, plus a button + GET dispatch + POST confirm handler added to 8 `card.php` files (Expedition needs none — see below).

**Tech Stack:** PHP 8.5 (Docker php-fpm), MariaDB, Dolibarr module/dictionary framework, jQuery.

## Global Constraints

- Indentation: tabs, not spaces (PSR-12 otherwise).
- Do not add `declare(strict_types=1)` — edits to existing core files.
- DB access only through `$this->db` / global `$db`; escape with `$db->escape()`/`(int)` casts; `GETPOST()` not raw `$_GET`/`$_POST`.
- Table prefix `llx_`; new dictionary table name `c_subtotals_texts`.
- No SQL inside loops.
- Commit message format: `NEW: Short description` (bug-fix tasks use `FIX:`); no issue number tracked for this feature.
- Never use `git commit --no-verify`.
- **Never add a `Co-Authored-By` trailer to any commit message, ever** — violated once on this branch already (a prior feature's Task 4) and had to be corrected; do not repeat it.
- Work happens directly on the existing branch (already checked out) — do not create a new branch, do not switch branches.
- Docker stack (mariadb/php-fpm/webserver) is already running, bind-mounting the repo root at `/application` in the `php-fpm` container. Host bare `php` is broken (pre-existing, unrelated `conf.php` path bug) — use `docker compose exec -T php-fpm php ...` (run from `/home/fred/dolibarr/dolibarr_dolidev`, the `docker-compose.yml` location) for any PHP execution. DB: `127.0.0.1:3306`, database `dolibarr`, user `dolibarr_user`, password `dolibarr_password`.
- PHPStan: `docker compose exec -T php-fpm php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G <paths>` from `/application/dolibarr` inside the container.

## Corrections to the design spec

`docs/superpowers/specs/2026-07-30-subtotals-text-lines-design.md` made two assumptions that deeper investigation (done while writing this plan) proved wrong. This plan implements the corrected understanding, not the spec's original text:

1. **`CommonSubtotal::addSubtotalLine()` / `updateSubtotalLine()` need no changes at all for `depth == 0`.** The spec assumed these methods would need new handling for the text-line case. Reading them line by line: every branch that treats `$depth` specially is gated by `$depth > 0` or `$depth < 0`; for `$depth == 0` both conditions are false, so the methods fall straight through to the generic per-module `addline()`/`updateline()` call with `$depth` (0) passed as `qty`, and `$rang` stays at its default `-1` (append at end) — exactly the same "no special placement rule matched" path already exercised today. No new task is needed for this file beyond adding the new `getPredefinedTexts()` method (Task 3).

2. **Expedition needs no new code at all**, not `addlinefree()`. Investigation of `htdocs/expedition/card.php` (lines 301-569) found shipments don't have a manual "Add title/subtotal line" button — title/subtotal lines are copied from the source order automatically when the shipment is created, via a loop that already checks only `special_code == SUBTOTALS_SPECIAL_CODE` (line 549, no `qty`-sign check) and passes the source line's `qty` straight through to `addSubtotalLine()`. Since text lines share the same `special_code`, this existing, untouched loop already copies them correctly once Commande (the order side) supports adding them — confirmed by combination with point 1 above (the shipping branch of `addSubtotalLine()` is likewise `depth`-sign-agnostic). Task 17 is therefore a verification-only task with no code changes.

## Task order and dependencies

Tasks 1-8 build the core mechanism (dictionary, trait method, admin toggle, 3 shared templates) and must land first. Tasks 9-16 (one per document type) each depend on Tasks 1-8 but are independent of each other. Task 17 (Expedition) depends on Task 10 (Commande) being done, since it copies text lines *from* an order.

---

### Task 1: Predefined-texts dictionary table

**Files:**
- Create: `htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.sql`
- Create: `htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.key.sql`
- Create: `htdocs/install/mysql/data/llx_c_subtotals_texts-subtotals.sql`

**Interfaces:**
- Produces: table `llx_c_subtotals_texts(rowid, entity, code, label, content, active)` with a unique index on `(entity, code)`, and 2 seed rows. Auto-loaded by `DolibarrModules::_load_tables('/install/mysql/', 'subtotals')` — already wired into `modSubtotals::init()` by a prior feature, no module code change needed for loading. Consumed by Task 3's `getPredefinedTexts()`.

- [ ] **Step 1: Create the table definition file**

`htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.sql`:

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
-- Dictionary of predefined multi-line texts usable as content of a subtotal free-text line
-- ========================================================================

create table llx_c_subtotals_texts
(
  rowid       integer AUTO_INCREMENT PRIMARY KEY,
  entity      integer DEFAULT 1 NOT NULL,
  code        varchar(32),
  label       varchar(255),
  content     mediumtext,
  active      tinyint DEFAULT 1 NOT NULL
)ENGINE=innodb;
```

- [ ] **Step 2: Create the unique-index file**

`htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.key.sql`:

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

ALTER TABLE llx_c_subtotals_texts ADD UNIQUE INDEX uk_c_subtotals_texts_code_entity (entity, code);
```

- [ ] **Step 3: Create the seed data file**

`htdocs/install/mysql/data/llx_c_subtotals_texts-subtotals.sql`:

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

INSERT INTO llx_c_subtotals_texts (entity, code, label, content, active) VALUES (__ENTITY__, 'DELIVERY_INSTRUCTIONS', 'Delivery instructions', 'Please deliver during business hours (8am-6pm).\nA signature will be required on receipt.', 1);
INSERT INTO llx_c_subtotals_texts (entity, code, label, content, active) VALUES (__ENTITY__, 'PAYMENT_TERMS_NOTE', 'Payment terms note', 'Payment is due within 30 days of the invoice date.\nLate payments may be subject to interest charges.', 1);
```

- [ ] **Step 4: Verify the SQL is syntactically valid against the dev database**

```bash
sed 's/__ENTITY__/1/g' dolibarr/htdocs/install/mysql/data/llx_c_subtotals_texts-subtotals.sql \
  > /tmp/seed_check.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < dolibarr/htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < dolibarr/htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.key.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  < /tmp/seed_check.sql

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "SELECT rowid, entity, code, label, content, active FROM llx_c_subtotals_texts ORDER BY rowid;"
```

Expected: no SQL errors, 2 rows printed with the full multi-line `content` values.

- [ ] **Step 5: Drop the table again**

This dry run only proves the SQL is valid — the real, repeatable load path is `_load_tables()`, exercised end-to-end in Task 2. Drop the table so Task 2 starts from a clean state:

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DROP TABLE llx_c_subtotals_texts;"
```

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.sql \
        htdocs/install/mysql/tables/llx_c_subtotals_texts-subtotals.key.sql \
        htdocs/install/mysql/data/llx_c_subtotals_texts-subtotals.sql
git commit -m "NEW: Add llx_c_subtotals_texts dictionary table"
```

---

### Task 2: Register the dictionary and add textarea support to dict.php

**Files:**
- Modify: `htdocs/core/modules/modSubtotals.class.php`
- Modify: `htdocs/admin/dict.php`

**Interfaces:**
- Consumes: Task 1's table.
- Produces: on module init/upgrade, `llx_c_subtotals_texts` exists (via the already-wired `_load_tables()` call); "Subtotals predefined texts" is listed and editable under Setup > Dictionaries, with `content` rendered as a `<textarea>`.

- [ ] **Step 1: Add a second dictionary entry to the constructor**

`htdocs/core/modules/modSubtotals.class.php` currently has (added by a prior feature):

```php
		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'subtotals',
			'tabname' => array("c_subtotals_phrases"),
			'tablib' => array("SubtotalsPredefinedPhrases"),
			'tabsql' => array('SELECT rowid, code, label, active, entity FROM '.MAIN_DB_PREFIX.'c_subtotals_phrases WHERE entity IN ('.getEntity('c_subtotals_phrases').')'),
			'tabsqlsort' => array("label ASC"),
			'tabfield' => array("code,label"),
			'tabfieldvalue' => array("code,label"),
			'tabfieldinsert' => array("code,label,entity"),
			'tabrowid' => array("rowid"),
			'tabcond' => array(isModEnabled('subtotals')),
			'tabhelp' => array(array()),
		);
```

Change it to a second, parallel entry per array key (order matters — this is the *second* element of every one of the parallel arrays):

```php
		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'subtotals',
			'tabname' => array("c_subtotals_phrases", "c_subtotals_texts"),
			'tablib' => array("SubtotalsPredefinedPhrases", "SubtotalsPredefinedTexts"),
			'tabsql' => array(
				'SELECT rowid, code, label, active, entity FROM '.MAIN_DB_PREFIX.'c_subtotals_phrases WHERE entity IN ('.getEntity('c_subtotals_phrases').')',
				'SELECT rowid, code, label, content, active, entity FROM '.MAIN_DB_PREFIX.'c_subtotals_texts WHERE entity IN ('.getEntity('c_subtotals_texts').')',
			),
			'tabsqlsort' => array("label ASC", "label ASC"),
			'tabfield' => array("code,label", "code,label,content"),
			'tabfieldvalue' => array("code,label", "code,label,content"),
			'tabfieldinsert' => array("code,label,entity", "code,label,content,entity"),
			'tabrowid' => array("rowid", "rowid"),
			'tabcond' => array(isModEnabled('subtotals'), isModEnabled('subtotals')),
			'tabhelp' => array(array(), array()),
		);
```

- [ ] **Step 2: Add textarea rendering for the `content` field**

In `htdocs/admin/dict.php`, inside `dictFieldList()` (the function shared by both the "create new dictionary row" and "edit existing row" forms), find:

```php
			} elseif (in_array($value, array('libelle_facture'))) {
```

Change to:

```php
			} elseif (in_array($value, array('libelle_facture', 'content'))) {
```

This is the only change needed in `dict.php`: the block it guards already does `print '<textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'. $value .'">'.(empty($obj->{$value}) ? '' : $obj->{$value}).'</textarea>';` generically for whatever field name matched, and the `if ($tabname == 'c_payment_term')` translation-lookup special case inside that block does not trigger for `$tabname == 'c_subtotals_texts'`, so `content` always falls through to the plain textarea — exactly what's needed.

- [ ] **Step 3: Run PHPStan**

```bash
cd dolibarr
docker compose exec -T php-fpm php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G /application/dolibarr/htdocs/core/modules/modSubtotals.class.php /application/dolibarr/htdocs/admin/dict.php
```

Expected: no new errors.

- [ ] **Step 4: Verify by re-running module init through a throwaway CLI script**

Create a temporary file `dolibarr/scripts/tmp_verify_texts_init.php` (NOT part of the commit — delete it after this step):

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
docker compose exec -T php-fpm php /application/dolibarr/scripts/tmp_verify_texts_init.php

mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "SHOW TABLES LIKE 'llx_c_subtotals_texts'; SELECT rowid, code, label, content, active FROM llx_c_subtotals_texts ORDER BY rowid;"
```

Expected: `init() result: 1`, no errors printed, `llx_c_subtotals_texts` exists with the 2 seeded rows and their multi-line `content`.

Delete the throwaway script:

```bash
rm dolibarr/scripts/tmp_verify_texts_init.php
```

- [ ] **Step 5: Verify the dictionary and textarea render correctly**

Open `https://localhost/admin/dict.php` in a browser (logged in as admin), confirm "Subtotals predefined texts" appears as its own dictionary tab, that its "Content" column shows a `<textarea>` when creating/editing a row, and that editing a row's multi-line content and saving preserves the line breaks.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/core/modules/modSubtotals.class.php htdocs/admin/dict.php
git commit -m "NEW: Register subtotals predefined texts dictionary with textarea support"
```

---

### Task 3: `CommonSubtotal::getPredefinedTexts()`

**Files:**
- Modify: `htdocs/subtotals/class/commonsubtotal.class.php`

**Interfaces:**
- Consumes: table `llx_c_subtotals_texts` (Task 1/2), `$this->db`.
- Produces: `public function getPredefinedTexts(): array<int,array{label:string,content:string}>` — keyed by `rowid`, each entry `array('label' => ..., 'content' => ...)`, only `active = 1` rows for the current entity, sorted by label. Used by Task 6 (`subtotal_create.tpl.php`) and Task 7 (`subtotal_edit.tpl.php`).

- [ ] **Step 1: Add the method to the trait**

In `htdocs/subtotals/class/commonsubtotal.class.php`, `getPredefinedPhrases()` (added by a prior feature) ends with `return $phrases;` then a closing `}`, immediately followed by the docblock for `getDisabledShippmentSubtotalLines()`. Insert the new method between them:

```php
	/**
	 * Retrieve the list of active predefined texts usable as content of a free-text line.
	 *
	 * @return array<int,array{label:string,content:string}>	Array keyed by rowid, each entry has a 'label' and 'content', sorted alphabetically by label
	 *
	 * @phan-suppress PhanUndeclaredProperty
	 */
	public function getPredefinedTexts()
	{
		$texts = array();

		$sql = "SELECT rowid, label, content FROM ".MAIN_DB_PREFIX."c_subtotals_texts";
		$sql .= " WHERE active = 1 AND entity IN (".getEntity('c_subtotals_texts').")";
		$sql .= " ORDER BY label ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$texts[(int) $obj->rowid] = array('label' => $obj->label, 'content' => $obj->content);
			}
		}

		return $texts;
	}

```

(Insert this new method directly above `getDisabledShippmentSubtotalLines()`'s docblock — the body of `getDisabledShippmentSubtotalLines()` itself is unchanged.)

- [ ] **Step 2: Run PHPStan**

```bash
cd dolibarr
docker compose exec -T php-fpm php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G /application/dolibarr/htdocs/subtotals/class/commonsubtotal.class.php
```

Expected: no new errors.

- [ ] **Step 3: Verify with a throwaway CLI script**

Create `dolibarr/scripts/tmp_verify_getpredefinedtexts.php` (NOT part of the commit):

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
$texts = $object->getPredefinedTexts();

echo "Predefined texts:\n";
print_r($texts);
```

Run it:

```bash
docker compose exec -T php-fpm php /application/dolibarr/scripts/tmp_verify_getpredefinedtexts.php
```

Expected output: an array of 2 entries keyed by rowid, each with `label` (`Delivery instructions` / `Payment terms note`) and the full multi-line `content`, alphabetical by label (`Delivery instructions` before `Payment terms note`).

Delete the throwaway script:

```bash
rm dolibarr/scripts/tmp_verify_getpredefinedtexts.php
```

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/subtotals/class/commonsubtotal.class.php
git commit -m "NEW: Add CommonSubtotal::getPredefinedTexts()"
```

---

### Task 4: Language keys

**Files:**
- Modify: `htdocs/langs/en_US/subtotals.lang`
- Modify: `htdocs/langs/fr_FR/subtotals.lang`

**Interfaces:**
- Produces: `AddTextLine`, `SubtotalsPredefinedTexts` (Task 2's `tablib`), `PredefinedText` (Task 6/7 select label), `SubtotalTextContent` (Task 6/7 textarea label), `SubtotalTextColumnTitle` (Task 5's admin-page column header).

- [ ] **Step 1: Add keys to `htdocs/langs/en_US/subtotals.lang`**

Current file (after the prior predefined-phrases feature) has:

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
SubtotalsPredefinedTexts=Subtotals predefined texts
SubtotalTextColumnTitle=Text
```

And:

```
#
# Card
#
AddTitleLine=Add title line
AddSubtotalLine=Add subtotal line
PredefinedPhrase=Predefined phrase
```

Change to:

```
#
# Card
#
AddTitleLine=Add title line
AddSubtotalLine=Add subtotal line
AddTextLine=Add text line
PredefinedPhrase=Predefined phrase
PredefinedText=Predefined text
SubtotalTextContent=Text content
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
SubtotalsPredefinedPhrases=Phrases prédéfinies de sous-totaux
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
SubtotalsPredefinedTexts=Textes prédéfinis de sous-totaux
SubtotalTextColumnTitle=Texte
```

Current `# Card` section:

```
#
# Card
#
AddTitleLine=Ajouter une ligne de titre
AddSubtotalLine=Ajouter une ligne de sous-total
PredefinedPhrase=Phrase prédéfinie
```

Change to:

```
#
# Card
#
AddTitleLine=Ajouter une ligne de titre
AddSubtotalLine=Ajouter une ligne de sous-total
AddTextLine=Ajouter une ligne de texte
PredefinedPhrase=Phrase prédéfinie
PredefinedText=Texte prédéfini
SubtotalTextContent=Contenu du texte
```

- [ ] **Step 3: Verify no duplicate keys**

```bash
cd dolibarr
for k in AddTextLine SubtotalsPredefinedTexts PredefinedText SubtotalTextContent SubtotalTextColumnTitle; do
  echo "$k:"
  grep -c "^$k=" htdocs/langs/en_US/subtotals.lang htdocs/langs/fr_FR/subtotals.lang
done
```

Expected: `1` for every file/key combination.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/langs/en_US/subtotals.lang htdocs/langs/fr_FR/subtotals.lang
git commit -m "NEW: Add language keys for subtotals free-text lines"
```

(The repo's pre-commit hook runs a language-key checker — treat any finding as real and fix it before proceeding.)

---

### Task 5: Admin toggle for text lines per document type

**Files:**
- Modify: `htdocs/admin/subtotals.php`

**Interfaces:**
- Produces: per-document-type constant `SUBTOTAL_TEXT_<ELEMENT>` (e.g. `SUBTOTAL_TEXT_PROPAL`), toggled the same way `SUBTOTAL_TITLE_<ELEMENT>` and `SUBTOTAL_<ELEMENT>` already are. Consumed by Tasks 9-16's button-gating conditions (`getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element))`).

- [ ] **Step 1: Add the "Text" column header**

In `htdocs/admin/subtotals.php`, find:

```php
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td width="1100">' . $langs->trans("Settings") . '</td>';
		print '<td class="center">' . $langs->trans("Title") . '</td>';
		print '<td class="center">' . $langs->trans("Subtotal") . '</td>';
		print '<td class="center">' . $langs->trans("MaxSubtotalLevel") . '</td>';
		print "</tr>\n";
```

Change to:

```php
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td width="1100">' . $langs->trans("Settings") . '</td>';
		print '<td class="center">' . $langs->trans("Title") . '</td>';
		print '<td class="center">' . $langs->trans("Subtotal") . '</td>';
		print '<td class="center">' . $langs->trans("SubtotalTextColumnTitle") . '</td>';
		print '<td class="center">' . $langs->trans("MaxSubtotalLevel") . '</td>';
		print "</tr>\n";
```

- [ ] **Step 2: Add the toggle switch per document type**

Find:

```php
		$constante_title = 'SUBTOTAL_TITLE_' . $const;
		$constante_subtotal = 'SUBTOTAL_' . $const;
		print '<!-- constant = ' . $constante_subtotal . ' -->' . "\n";
		print '<tr class="oddeven">';
		print '<td>';
		if (isset($desc['old_pdf'])) {
			print $form->textwithpicto($langs->trans($desc['key']), $langs->trans("NotSupportedByAllPDF", $desc['old_pdf']));
		} else {
			print $langs->trans($desc['key']);
		}
		print '</td>';

		print '<td class="center">';
		$value_title = getDolGlobalInt($constante_title, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_title . '&token=' . newToken() . '">';
		print $value_title == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center">';
		$value_subtotal = getDolGlobalInt($constante_subtotal, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_subtotal . '&token=' . newToken() . '">';
		print $value_subtotal == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center nowraponall">';
```

Change to:

```php
		$constante_title = 'SUBTOTAL_TITLE_' . $const;
		$constante_subtotal = 'SUBTOTAL_' . $const;
		$constante_text = 'SUBTOTAL_TEXT_' . $const;
		print '<!-- constant = ' . $constante_subtotal . ' -->' . "\n";
		print '<tr class="oddeven">';
		print '<td>';
		if (isset($desc['old_pdf'])) {
			print $form->textwithpicto($langs->trans($desc['key']), $langs->trans("NotSupportedByAllPDF", $desc['old_pdf']));
		} else {
			print $langs->trans($desc['key']);
		}
		print '</td>';

		print '<td class="center">';
		$value_title = getDolGlobalInt($constante_title, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_title . '&token=' . newToken() . '">';
		print $value_title == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center">';
		$value_subtotal = getDolGlobalInt($constante_subtotal, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_subtotal . '&token=' . newToken() . '">';
		print $value_subtotal == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center">';
		$value_text = getDolGlobalInt($constante_text, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_text . '&token=' . newToken() . '">';
		print $value_text == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center nowraponall">';
```

No other change is needed: the generic action handler at the top of the file (`if (preg_match('/^SUBTOTAL_.*$/', $action)) { ... }`) already toggles *any* `SUBTOTAL_*` constant generically, so `SUBTOTAL_TEXT_PROPAL` etc. work through the exact same click-to-toggle link with zero new logic. The max-depth column/`$can_modify` logic is intentionally left untouched — text lines have no level, so enabling only the text toggle should not require or affect a max-depth value.

- [ ] **Step 3: Run PHPStan**

```bash
cd dolibarr
docker compose exec -T php-fpm php vendor/bin/phpstan analyze -a dev/build/phpstan/bootstrap.php --memory-limit 4G /application/dolibarr/htdocs/admin/subtotals.php
```

Expected: no new errors.

- [ ] **Step 4: Manual browser verification**

Open `https://localhost/admin/subtotals.php`, confirm a new "Text" column with toggle switches appears for every listed document type, and that clicking one toggles it (page reloads, switch flips, and `SELECT * FROM llx_const WHERE name LIKE 'SUBTOTAL_TEXT_%'` via mysql shows the row).

- [ ] **Step 5: Commit**

```bash
cd dolibarr
git add htdocs/admin/subtotals.php
git commit -m "NEW: Add per-document-type admin toggle for subtotals text lines"
```

---

### Task 6: Text-line creation form

**Files:**
- Modify: `htdocs/core/tpl/subtotal_create.tpl.php`

**Interfaces:**
- Consumes: `getPredefinedTexts()` (Task 3, called on `$object`, NOT `$this` — this file is `require`d from procedural `card.php` scope in all 8 standard document types, so `$this` is undefined here; the existing `title`/`subtotal` branches already use `$object->getPossibleLevels($langs)` / `$object->getPossibleTitles()`, confirming this file's established convention), `PredefinedText` / `SubtotalTextContent` lang keys (Task 4).
- Produces: submitted fields `subtotalpredefinedtext` (never read server-side, pure UI convenience) and `subtotaltextcontent` (the actual field read by Tasks 9-16's `confirm_addtextline` handlers).

- [ ] **Step 1: Add the `text` branch**

Current code (`title`/`subtotal` branches, after a prior feature added the predefined-phrase select to `title` and its final-review fix corrected the `onchange` guard to also reject the select's blank-option sentinel value `-1`):

```php
if ($type == 'title') {
	$formquestion = array();

	$predefinedphrases = $object->getPredefinedPhrases();
	if (!empty($predefinedphrases)) {
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedphrase',
			'label' => $langs->trans("PredefinedPhrase"),
			'values' => $predefinedphrases,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="var v = jQuery(this).val(); if (v && v != \'-1\') { jQuery(\'#subtotallinedesc\').val(v); }"',
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
	$formquestion = array(
		array('type' => 'select', 'name' => 'subtotaltitleline', 'label' => $langs->trans("CorrespondingTitleLine"), 'values' => $titles, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'subtotalshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
	);
}
```

Add a new `elseif ($type == 'text')` branch right after the `subtotal` branch:

```php
if ($type == 'title') {
	$formquestion = array();

	$predefinedphrases = $object->getPredefinedPhrases();
	if (!empty($predefinedphrases)) {
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedphrase',
			'label' => $langs->trans("PredefinedPhrase"),
			'values' => $predefinedphrases,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="var v = jQuery(this).val(); if (v && v != \'-1\') { jQuery(\'#subtotallinedesc\').val(v); }"',
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
	$formquestion = array(
		array('type' => 'select', 'name' => 'subtotaltitleline', 'label' => $langs->trans("CorrespondingTitleLine"), 'values' => $titles, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'subtotalshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
	);
} elseif ($type == 'text') {
	$formquestion = array();

	$predefinedtexts = $object->getPredefinedTexts();
	if (!empty($predefinedtexts)) {
		$predefinedtextvalues = array();
		$predefinedtextsmap = array();
		foreach ($predefinedtexts as $rowid => $text) {
			$predefinedtextvalues[$rowid] = $text['label'];
			$predefinedtextsmap[$rowid] = $text['content'];
		}
		print '<script>var subtotalPredefinedTextsMap = ' . json_encode($predefinedtextsmap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedtext',
			'label' => $langs->trans("PredefinedText"),
			'values' => $predefinedtextvalues,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="var v = subtotalPredefinedTextsMap[jQuery(this).val()]; if (v !== undefined) { jQuery(\'#subtotaltextcontent\').val(v); }"',
		);
	}

	$formquestion[] = array('type' => 'textarea', 'name' => 'subtotaltextcontent', 'label' => $langs->trans("SubtotalTextContent"));
}
```

`subtotalpredefinedtext` is never read server-side — pure client-side convenience, same reasoning as the phrases feature. `subtotaltextcontent` is the field Tasks 9-16 actually read.

- [ ] **Step 2: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/core/tpl/subtotal_create.tpl.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual browser verification (deferred to Task 9)**

This template has no direct URL of its own — it's only reachable via a document's "Add text line" button, added in Task 9 (Propal). Defer live verification to Task 9's manual test.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/core/tpl/subtotal_create.tpl.php
git commit -m "NEW: Add text-line branch to subtotal line creation form"
```

---

### Task 7: Text-line inline edit form

**Files:**
- Modify: `htdocs/core/tpl/subtotal_edit.tpl.php`

**Interfaces:**
- Consumes: `$this->getPredefinedTexts()` (Task 3 — this file IS reached from inside `CommonObject::printObjectLine()`, a real class method, via `objectline_edit.tpl.php`, so `$this` correctly refers to the document object here, unlike `subtotal_create.tpl.php`), `PredefinedText` lang key (Task 4).
- Produces: no new interface for other tasks — leaf UI change alongside Task 6.

- [ ] **Step 1: Compute a 3-way `$line_type` and branch the description field**

Current code:

```php
// Line type
$line_type = $line->qty > 0 ? 'title' : 'subtotal';
```

Change to:

```php
// Line type
$line_type = $line->qty > 0 ? 'title' : ($line->qty < 0 ? 'subtotal' : 'text');
```

Then find the description-input block (already modified once by a prior feature to add the predefined-phrase select for `title`, whose final-review fix corrected the `onchange` guard to also reject the select's blank-option sentinel value `-1`):

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
			$predefinedphrases = $this->getPredefinedPhrases();
			if (!empty($predefinedphrases)) {
				print $form->selectarray('line_predefinedphrase', $predefinedphrases, '', 1, 0, 0, 'onchange="var v = jQuery(this).val(); if (v && v != \'-1\') { jQuery(\'#line_desc\').val(v); }"', 0, 0, 0, '', 'minwidth100');
			}
		}
		$depth_array = $this->getPossibleLevels($langs);
		print $form->selectarray('line_depth', $depth_array, abs($line->qty), 0, 0, 0, '', 0, 0, $disabled);
```

Replace with (the `text` type gets its own `<textarea>` instead of the shared single-line `<input>`, since a text line's whole point is multi-line content — the `title`/`subtotal` input stays exactly as-is):

```php
	if (!$situationinvoicelinewithparent) {
		if ($line_type == 'text') {
			print '<textarea name="line_desc" class="marginrightonly" id="line_desc" rows="4" cols="40">';
			print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description;
			print '</textarea>';

			$predefinedtexts = $this->getPredefinedTexts();
			if (!empty($predefinedtexts)) {
				$predefinedtextvalues = array();
				$predefinedtextsmap = array();
				foreach ($predefinedtexts as $rowid => $text) {
					$predefinedtextvalues[$rowid] = $text['label'];
					$predefinedtextsmap[$rowid] = $text['content'];
				}
				print '<script>var subtotalEditPredefinedTextsMap = ' . json_encode($predefinedtextsmap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
				print $form->selectarray('line_predefinedtext', $predefinedtextvalues, '', 1, 0, 0, 'onchange="var v = subtotalEditPredefinedTextsMap[jQuery(this).val()]; if (v !== undefined) { jQuery(\'#line_desc\').val(v); }"', 0, 0, 0, '', 'minwidth100');
			}
			$disabled = 0;
		} else {
			print '<input type="text" name="line_desc" class="marginrightonly" id="line_desc" value="';
			print GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description . '"';
			$disabled = 0;
			if ($line_type == 'subtotal') {
				print ' readonly="readonly"';
				$disabled = 1;
			}
			print '>';
			if ($line_type == 'title') {
				$predefinedphrases = $this->getPredefinedPhrases();
				if (!empty($predefinedphrases)) {
					print $form->selectarray('line_predefinedphrase', $predefinedphrases, '', 1, 0, 0, 'onchange="var v = jQuery(this).val(); if (v && v != \'-1\') { jQuery(\'#line_desc\').val(v); }"', 0, 0, 0, '', 'minwidth100');
				}
			}
		}
		$depth_array = $this->getPossibleLevels($langs);
		print $form->selectarray('line_depth', $depth_array, abs($line->qty), 0, 0, 0, '', 0, 0, $disabled);
```

`line_predefinedtext` is never read server-side, same convention as `line_predefinedphrase`. `line_depth` still renders for `text` lines (harmless — `getPossibleLevels()` and the depth select are generic UI that already exists for every line type in this file; the actual submitted `line_depth` value is not used by `updateSubtotalLine()`'s text-line path since Task 3's investigation showed `$depth` there is only meaningfully read for the `title`/`subtotal` placement rules — Tasks 9-16's `confirm_updatetextline`-equivalent handling always passes `0` explicitly, not the posted `line_depth`, exactly like `confirm_addtextline` does on creation).

- [ ] **Step 2: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/core/tpl/subtotal_edit.tpl.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual browser verification (deferred to Task 9)**

Same reasoning as Task 6 — this template is only reachable by editing an existing text line, which requires Task 9's "Add text line" button to create one first.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/core/tpl/subtotal_edit.tpl.php
git commit -m "NEW: Add text-line branch to subtotal line inline edit form"
```

---

### Task 8: Text-line display

**Files:**
- Modify: `htdocs/core/tpl/subtotal_view.tpl.php`

**Interfaces:**
- Consumes: nothing new from earlier tasks (pure display of `$line->desc`).
- Produces: no new interface — leaf UI change.

- [ ] **Step 1: Add the `qty == 0` branch**

Current code has `if ($line->qty > 0) { ... } elseif ($line->qty < 0) { ... }` with no `else`, followed unconditionally by the edit/delete/move-icon rendering. Find the closing of the `elseif ($line->qty < 0)` block:

```php
	<?php
	if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
		echo '<td class="linecolamount nowrap right"';
		echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"';
		echo '>';
		echo $this->getSubtotalLineMulticurrencyAmount($line);
		echo '</td>';
	}
	?>
<?php }

if ($this->status == 0) {
```

Change to (adding an `elseif ($line->qty == 0)` branch between the existing `elseif ($line->qty < 0)` block and the `if ($this->status == 0)` icon section — `$colspan` here mirrors the `qty < 0` branch's computation earlier in the same file, since a text line also spans the full description width with no price/qty columns):

```php
	<?php
	if (isModEnabled('multicurrency') && $object->multicurrency_code != $conf->currency) {
		echo '<td class="linecolamount nowrap right"';
		echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"';
		echo '>';
		echo $this->getSubtotalLineMulticurrencyAmount($line);
		echo '</td>';
	}
	?>
<?php } elseif ($line->qty == 0) {
	// Base colspan if there is no module activated to display line correctly
	$colspan = 4;  // linecoldescription, linecolvat, linecoluht, linecolqty

	if (isModEnabled("multicurrency") && $this->multicurrency_code && $this->multicurrency_code != $conf->currency) {
		$colspan++;
	}
	// Handling colspan if MAIN_NO_INPUT_PRICE_WITH_TAX conf is enabled
	if (!empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
		$colspan++;
	}
	if (isModEnabled("multicurrency") && $this->multicurrency_code && $this->multicurrency_code != $conf->currency && !empty($inputalsopricewithtax) && !getDolGlobalInt('MAIN_NO_INPUT_PRICE_WITH_TAX')) {
		$colspan++;
	}

	if (property_exists($this, 'situation_cycle_ref') && isset($this->situation_cycle_ref) && $this->situation_cycle_ref) {
		$colspan += 2;
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$colspan += 1;
		}
	}

	// Handling colspan if margin module is enabled
	if (!empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande')) && isModEnabled('margin') && empty($user->socid)) {
		if ($user->hasRight('margins', 'creer')) {
			$colspan += 1;
		}
		if (getDolGlobalString('DISPLAY_MARGIN_RATES') && $user->hasRight('margins', 'liretous')) {
			$colspan += 1;
		}
		if (getDolGlobalString('DISPLAY_MARK_RATES') && $user->hasRight('margins', 'liretous')) {
			$colspan += 1;
		}
	}

	// Handling colspan if PRODUCT_USE_UNITS conf is enabled
	if (getDolGlobalString('PRODUCT_USE_UNITS')) {
		$colspan += 1;
	}
	// Handling colspan if supplier object
	if (in_array($object->element, ['supplier_proposal'])) {
		$colspan += 1;
	}
	?>
	<td class="linecollabel" colspan="<?php echo $colspan ?>"><?php echo nl2br($line->desc); ?></td>
<?php }

if ($this->status == 0) {
```

The text branch uses `nl2br($line->desc)` (unlike the `title`/`subtotal` branches' plain `echo $line->desc`) since text-line content is multi-line and Dolibarr descriptions are stored as plain newline-separated text, not HTML — `nl2br()` is the standard Dolibarr convention for rendering a stored plain-text multi-line value as HTML (the same pattern used for e.g. private/public notes elsewhere in core). No background color banding (`$line_color` is computed once at the top of the file via `getSubtotalColors($line->qty)`, which returns an empty string for `qty == 0` since no `SUBTOTAL_BACK_COLOR_LEVEL_0` constant is ever set — this already renders as no background color with zero additional code).

- [ ] **Step 2: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/core/tpl/subtotal_view.tpl.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Manual browser verification (deferred to Task 9)**

Same reasoning as Tasks 6/7.

- [ ] **Step 4: Commit**

```bash
cd dolibarr
git add htdocs/core/tpl/subtotal_view.tpl.php
git commit -m "NEW: Add text-line display branch"
```

---

### Task 9: Propal integration

**Files:**
- Modify: `htdocs/comm/propal/card.php`

**Interfaces:**
- Consumes: Tasks 3-8 (trait method, admin toggle, 3 templates).
- Produces: none for other tasks — this is the first of 8 independent per-document-type integrations; Tasks 10-16 replicate the identical pattern on their own files.

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 2818):

```php
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

Change to:

```php
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code (around line 1154-1252, the `confirm_addtitleline`/`confirm_addsubtotalline` handlers):

```php
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

Insert a new handler immediately before this line (i.e. right after whatever `} elseif (...) {` block precedes it — search for the exact `confirm_addtitleline` line above and insert your new block directly above it):

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 3599-3618, the `$url_button` array):

```php
					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('propal') && $object->status == Propal::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => '/comm/propal/card.php?id=' . $object->id . '&action=add_title_line&token=' . newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('propal') && $object->status == Propal::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => '/comm/propal/card.php?id=' . $object->id . '&action=add_subtotal_line&token=' . newToken()
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

Also update the outer `if` condition that gates this whole block (a few lines above, currently `if ($object->status == Propal::STATUS_DRAFT && isModEnabled('subtotals') && (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)))) {`):

```php
				if ($object->status == Propal::STATUS_DRAFT && isModEnabled('subtotals')
					&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
					$langs->load('subtotals');

					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('propal') && $object->status == Propal::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => '/comm/propal/card.php?id=' . $object->id . '&action=add_title_line&token=' . newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('propal') && $object->status == Propal::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => '/comm/propal/card.php?id=' . $object->id . '&action=add_subtotal_line&token=' . newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('propal') && $object->status == Propal::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element))),
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTextLine'),
						'url' => '/comm/propal/card.php?id=' . $object->id . '&action=add_text_line&token=' . newToken()
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/comm/propal/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_PROPAL'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_PROPAL', '1', 'chaine', 1);"
```

In a browser: open a draft quote, confirm "Add text line" appears in the Subtotal dropdown button, click it, confirm the predefined-text dropdown and textarea render (Task 6), pick a predefined text and confirm it fills the textarea, edit it, save — confirm the line appears full-width with no background color and no price/qty columns (Task 8). Edit the line inline — confirm the textarea + predefined-text dropdown render there too (Task 7), edit the content, save, confirm it persists. Delete the line — confirm it deletes cleanly with no "delete corresponding line" prompt (that prompt only ever applies to title lines).

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/comm/propal/card.php
git commit -m "NEW: Add text-line support to Propal"
```

---

### Task 10: Commande integration

**Files:**
- Modify: `htdocs/commande/card.php`

**Interfaces:**
- Consumes: Tasks 3-8. Consumed by Task 17 (Expedition verification copies FROM a Commande).

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 2924):

```php
		if ($action == 'add_title_line') {
			$langs->load('subtotals');
			$type = 'title';
			$depth_array = $object->getPossibleLevels($langs);
			include DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_subtotal_line') {
			$langs->load('subtotals');
			$type = 'subtotal';
			$titles = $object->getPossibleTitles();
			include DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		}
```

Change to:

```php
		if ($action == 'add_title_line') {
			$langs->load('subtotals');
			$type = 'title';
			$depth_array = $object->getPossibleLevels($langs);
			include DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_subtotal_line') {
			$langs->load('subtotals');
			$type = 'subtotal';
			$titles = $object->getPossibleTitles();
			include DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_text_line') {
			$langs->load('subtotals');
			$type = 'text';
			include DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code has `} elseif ($action == 'confirm_addtitleline' && $usercancreate) {` (around line 858). Insert immediately before it:

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 3489-3512):

```php
			// Subtotal
			if ($object->status == Commande::STATUS_DRAFT && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)))) {
				$langs->load('subtotals');

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('order') && $object->status == Commande::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/commande/card.php?id=' . $object->id . '&action=add_title_line&token=' . newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('order') && $object->status == Commande::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/commande/card.php?id=' . $object->id . '&action=add_subtotal_line&token=' . newToken()
				);
				print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
			}
```

Change to:

```php
			// Subtotal
			if ($object->status == Commande::STATUS_DRAFT && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
				$langs->load('subtotals');

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('order') && $object->status == Commande::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/commande/card.php?id=' . $object->id . '&action=add_title_line&token=' . newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('order') && $object->status == Commande::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/commande/card.php?id=' . $object->id . '&action=add_subtotal_line&token=' . newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('order') && $object->status == Commande::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTextLine'),
					'url' => '/commande/card.php?id=' . $object->id . '&action=add_text_line&token=' . newToken()
				);
				print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
			}
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/commande/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_COMMANDE'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_COMMANDE', '1', 'chaine', 1);"
```

Same manual test as Task 9's Step 5, on a draft customer order. Keep this order around afterward — Task 17 uses it to verify the Expedition auto-copy path.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/commande/card.php
git commit -m "NEW: Add text-line support to Commande"
```

---

### Task 11: Facture integration

**Files:**
- Modify: `htdocs/compta/facture/card.php`

**Interfaces:**
- Consumes: Tasks 3-8.

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 5334):

```php
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

Change to:

```php
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code has `} elseif ($action == 'confirm_addtitleline' && $usercancreate) {` (around line 2433). Insert immediately before it. Note this file's redirect uses `?facid=`, not `?id=`:

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id);
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 6740-6762):

```php
			// Subtotal
			if ($object->status == Facture::STATUS_DRAFT && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)))) {
				$langs->load("subtotals");

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/compta/facture/card.php?facid='.$object->id.'&action=add_title_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT  && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/compta/facture/card.php?facid='.$object->id.'&action=add_subtotal_line&token='.newToken()
				);
				print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

Change to:

```php
			// Subtotal
			if ($object->status == Facture::STATUS_DRAFT && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
				$langs->load("subtotals");

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/compta/facture/card.php?facid='.$object->id.'&action=add_title_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT  && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/compta/facture/card.php?facid='.$object->id.'&action=add_subtotal_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTextLine'),
					'url' => '/compta/facture/card.php?facid='.$object->id.'&action=add_text_line&token='.newToken()
				);
				print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/compta/facture/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_FACTURE'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_FACTURE', '1', 'chaine', 1);"
```

Same manual test as Task 9's Step 5, on a draft customer invoice.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/compta/facture/card.php
git commit -m "NEW: Add text-line support to Facture"
```

---

### Task 12: FactureRec integration

**Files:**
- Modify: `htdocs/compta/facture/card-rec.php`

**Interfaces:**
- Consumes: Tasks 3-8.

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 1577):

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

Change to:

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code has `} elseif ($action == 'confirm_addtitleline' && $usercancreate) {` (around line 840). Insert immediately before it. Note: unlike Propal/Commande/Facture, this file's `confirm_addtitleline` does NOT call `generateDocument()` (recurring-invoice templates have no PDF) — mirror that:

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 2198-2221):

```php
			// Subtotal
			if (empty($object->suspended) && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)))) {
				$langs->load("subtotals");

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/compta/facture/card-rec.php?id='.$object->id.'&action=add_title_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/compta/facture/card-rec.php?id='.$object->id.'&action=add_subtotal_line&token='.newToken()
				);
			print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
			}
```

Change to:

```php
			// Subtotal
			if (empty($object->suspended) && isModEnabled('subtotals')
				&& (getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
				$langs->load("subtotals");

				$url_button = array();

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TITLE_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTitleLine'),
					'url' => '/compta/facture/card-rec.php?id='.$object->id.'&action=add_title_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddSubtotalLine'),
					'url' => '/compta/facture/card-rec.php?id='.$object->id.'&action=add_subtotal_line&token='.newToken()
				);

				$url_button[] = array(
					'lang' => 'subtotals',
					'enabled' => (isModEnabled('invoice') && $object->status == Facture::STATUS_DRAFT && getDolGlobalInt('SUBTOTAL_TEXT_'.strtoupper($object->element))),
					'perm' => (bool) $usercancreate,
					'label' => $langs->trans('AddTextLine'),
					'url' => '/compta/facture/card-rec.php?id='.$object->id.'&action=add_text_line&token='.newToken()
				);
			print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
			}
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/compta/facture/card-rec.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_FACTUREREC'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_FACTUREREC', '1', 'chaine', 1);"
```

Same manual test as Task 9's Step 5, on a recurring-invoice template.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/compta/facture/card-rec.php
git commit -m "NEW: Add text-line support to FactureRec"
```

---

### Task 13: SupplierProposal integration

**Files:**
- Modify: `htdocs/supplier_proposal/card.php`

**Interfaces:**
- Consumes: Tasks 3-8.

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 1907):

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

Change to:

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code has `} elseif ($action == 'confirm_addtitleline' && $usercancreate) {` (around line 666). Insert immediately before it:

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 2317-2340):

```php
			if ($action != 'statut' && $action != 'editline') {
				// Subtotal
				if ($object->status == SupplierProposal::STATUS_DRAFT && isModEnabled('subtotals') && getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element))) {
					$langs->load('subtotals');

					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == SupplierProposal::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == SupplierProposal::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

Change to (note the gating condition here only checks `SUBTOTAL_TITLE_*`, not an OR of title/subtotal like the other files — add the text check the same way):

```php
			if ($action != 'statut' && $action != 'editline') {
				// Subtotal
				if ($object->status == SupplierProposal::STATUS_DRAFT && isModEnabled('subtotals')
					&& (getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
					$langs->load('subtotals');

					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == SupplierProposal::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == SupplierProposal::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == SupplierProposal::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTextLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_text_line'], true)
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
```

This also fixes a latent bug: the original condition only checked `SUBTOTAL_TITLE_*`, so if an admin enabled only the "Subtotal" toggle (not "Title") for SupplierProposal, neither button would show at all. Widening it to an OR of all three (matching every other document type's convention) is a minimal, in-scope correction — not a new bug, just aligning this one file with the pattern already used everywhere else.

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/supplier_proposal/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_SUPPLIER_PROPOSAL'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_SUPPLIER_PROPOSAL', '1', 'chaine', 1);"
```

Same manual test as Task 9's Step 5, on a draft supplier proposal.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/supplier_proposal/card.php
git commit -m "NEW: Add text-line support to SupplierProposal"
```

---

### Task 14: CommandeFournisseur integration

**Files:**
- Modify: `htdocs/fourn/commande/card.php`

**Interfaces:**
- Consumes: Tasks 3-8.

- [ ] **Step 1: Add the GET action dispatch**

Current code (around line 2401):

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require  DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
	}
```

Change to:

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require  DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
	}
```

- [ ] **Step 2: Add the POST confirm handler**

Current code has `} elseif ($action == 'confirm_addtitleline' && $usercancreate) {` (around line 473). Insert immediately before it. Note this file's redirect uses `dolBuildUrl()`:

```php
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.dolBuildUrl($_SERVER["PHP_SELF"], ['id' => $id]));
		exit();
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
```

- [ ] **Step 3: Add the "Add text line" button**

Current code (around line 2856-2879):

```php
				// Subtotal
				if ($object->status == CommandeFournisseur::STATUS_DRAFT && isModEnabled('subtotals') && getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element))) {
					$langs->load('subtotals');

					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == CommandeFournisseur::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == CommandeFournisseur::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
				}
```

Change to (same latent-bug correction as Task 13 — widen the title-only gate to an OR including subtotal/text):

```php
				// Subtotal
				if ($object->status == CommandeFournisseur::STATUS_DRAFT && isModEnabled('subtotals')
					&& (getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
					$langs->load('subtotals');

					$url_button = array();

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == CommandeFournisseur::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTitleLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == CommandeFournisseur::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => $object->status == CommandeFournisseur::STATUS_DRAFT,
						'perm' => (bool) $usercancreate,
						'label' => $langs->trans('AddTextLine'),
						'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_text_line'], true)
					);

					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
				}
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/fourn/commande/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Enable the toggle and manually verify end-to-end**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name = 'SUBTOTAL_TEXT_ORDER_SUPPLIER'; INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TEXT_ORDER_SUPPLIER', '1', 'chaine', 1);"
```

Same manual test as Task 9's Step 5, on a draft supplier order.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/fourn/commande/card.php
git commit -m "NEW: Add text-line support to CommandeFournisseur"
```

---

### Task 15: FactureFournisseur integration and title/subtotal bug fix

**Files:**
- Modify: `htdocs/fourn/facture/card.php`

**Interfaces:**
- Consumes: Tasks 3-8.

**This file currently has NO `confirm_addtitleline`/`confirm_addsubtotalline` handlers at all**, despite having the buttons and the GET dispatch — the buttons are dead ends today. This task fixes both, alongside adding the text-line handler, so all three work consistently.

- [ ] **Step 1: Add the GET action dispatch for text**

Current code (around line 3364):

```php
		// Subtotal line form
		if ($action == 'add_title_line') {
			$langs->load('subtotals');
			$type = 'title';
			$depth_array = $object->getPossibleLevels($langs);
			require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_subtotal_line') {
			$langs->load('subtotals');
			$type = 'subtotal';
			$titles = $object->getPossibleTitles();
			require  DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		}
```

Change to:

```php
		// Subtotal line form
		if ($action == 'add_title_line') {
			$langs->load('subtotals');
			$type = 'title';
			$depth_array = $object->getPossibleLevels($langs);
			require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_subtotal_line') {
			$langs->load('subtotals');
			$type = 'subtotal';
			$titles = $object->getPossibleTitles();
			require  DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		} elseif ($action == 'add_text_line') {
			$langs->load('subtotals');
			$type = 'text';
			require DOL_DOCUMENT_ROOT . '/core/tpl/subtotal_create.tpl.php';
		}
```

- [ ] **Step 2: Add the three missing POST confirm handlers**

This file's Actions section uses `$usercancreate` for permission checks (confirmed: `confirm_deleteline`, `confirm_paid`, `confirm_paid_partially` all use `&& $usercancreate`) and `header('Location: ...')`/`exit()` after a PDF-regeneration block matching the `setabsolutediscount` handler's pattern (which ends with a bare `}` closing brace, no explicit `header()`/`exit()` — that handler falls through to whatever comes after it in the elseif chain rather than redirecting, which is NOT the pattern to copy for these new handlers; instead mirror Propal's `confirm_addtitleline`/`confirm_addsubtotalline`/`confirm_addtextline` exactly, using `?id=` since this file already builds its buttons with `dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, ...])`).

Find the `setabsolutediscount` handler (around lines 541-608), which ends:

```php
			$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
```

Insert the three new handlers immediately after this block's closing `}` (i.e. right after `	}` that closes the `setabsolutediscount` elseif, before whatever `} elseif (...)` comes next in the file):

```php
			$result = $object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} elseif ($action == 'confirm_addtitleline' && $usercancreate) {
		// Handling adding a new title line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotallinedesc', 'alphanohtml');
		$depth = GETPOSTINT('subtotallinelevel') ?? 1;

		$subtotal_options = array();

		foreach (FactureFournisseur::$TITLE_OPTIONS as $option) {
			$value = GETPOST($option, 'alphanohtml');
			if ($value) {
				$subtotal_options[$option] = $value == 'on' ? 1 : $value;
			}
		}

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, (int) $depth, $subtotal_options);

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.dolBuildUrl($_SERVER["PHP_SELF"], ['id' => $id]));
		exit();
	} elseif ($action == 'confirm_addsubtotalline' && $usercancreate) {
		// Handling adding a new subtotal line for subtotals module

		$langs->load('subtotals');

		$choosen_line = GETPOST('subtotaltitleline', 'alphanohtml');
		foreach ($object->lines as $line) {
			if ($line->desc == $choosen_line && $line->special_code == SUBTOTALS_SPECIAL_CODE) {
				$desc = $line->desc;
				$depth = -$line->qty;
			}
		}

		$subtotal_options = array();

		foreach (FactureFournisseur::$SUBTOTAL_OPTIONS as $option) {
			$value = GETPOST($option, 'alphanohtml');
			if ($value) {
				$subtotal_options[$option] = $value == 'on' ? 1 : $value;
			}
		}

		// Insert line
		if (isset($desc) && isset($depth)) {
			$result = $object->addSubtotalLine($langs, $desc, (int) $depth, $subtotal_options);
		} else {
			$result = -1;
			$object->errors[] = $langs->trans("CorrespondingTitleNotFound");
		}

		if ($result >= 0) {
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.dolBuildUrl($_SERVER["PHP_SELF"], ['id' => $id]));
		exit();
	} elseif ($action == 'confirm_addtextline' && $usercancreate) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.dolBuildUrl($_SERVER["PHP_SELF"], ['id' => $id]));
		exit();
```

(The original file's `} elseif ('setabsolutediscount' handler's closing brace...` — whatever `elseif` block originally followed `setabsolutediscount` in the file now follows these three new blocks instead; do not remove or alter it, only insert before it.)

- [ ] **Step 3: Add the "Add text line" button, and fix the button-gating condition**

Current code (around line 4245-4258):

```php
					// Subtotal
					if ($object->status === FactureFournisseur::STATUS_DRAFT && isModEnabled('subtotals') && getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element))) {
						$langs->load('subtotals');

						$url_button = array();

						$url_button[] = array(
							'lang' => 'subtotals',
							'enabled' => true,
							'perm' => (bool) $usercancreate,
							'label' => $langs->trans('AddTitleLine'),
							'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
						);

						$url_button[] = array(
							'lang' => 'subtotals',
							'enabled' => true,
							'perm' => (bool) $usercancreate,
							'label' => $langs->trans('AddSubtotalLine'),
							'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
						);

						print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
					}
```

Change to:

```php
					// Subtotal
					if ($object->status === FactureFournisseur::STATUS_DRAFT && isModEnabled('subtotals')
						&& (getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
						$langs->load('subtotals');

						$url_button = array();

						$url_button[] = array(
							'lang' => 'subtotals',
							'enabled' => true,
							'perm' => (bool) $usercancreate,
							'label' => $langs->trans('AddTitleLine'),
							'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_title_line'], true)
						);

						$url_button[] = array(
							'lang' => 'subtotals',
							'enabled' => true,
							'perm' => (bool) $usercancreate,
							'label' => $langs->trans('AddSubtotalLine'),
							'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_subtotal_line'], true)
						);

						$url_button[] = array(
							'lang' => 'subtotals',
							'enabled' => true,
							'perm' => (bool) $usercancreate,
							'label' => $langs->trans('AddTextLine'),
							'url' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'action' => 'add_text_line'], true)
						);

						print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
					}
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/fourn/facture/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Manually verify all three buttons now work, and the text line specifically**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name IN ('SUBTOTAL_TITLE_INVOICE_SUPPLIER', 'SUBTOTAL_INVOICE_SUPPLIER', 'SUBTOTAL_TEXT_INVOICE_SUPPLIER'); INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TITLE_INVOICE_SUPPLIER', '1', 'chaine', 1), ('SUBTOTAL_INVOICE_SUPPLIER', '1', 'chaine', 1), ('SUBTOTAL_TEXT_INVOICE_SUPPLIER', '1', 'chaine', 1);"
```

On a draft supplier invoice: click "Add title line", submit — confirm it now actually creates a title line (previously a dead end). Click "Add subtotal line" on that title, submit — confirm it now actually creates the closing subtotal line. Then run the same text-line test as Task 9's Step 5.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/fourn/facture/card.php
git commit -m "FIX: Add missing title/subtotal line handlers and add text-line support to FactureFournisseur"
```

---

### Task 16: Fichinter integration and subtotal bug fix

**Files:**
- Modify: `htdocs/fichinter/card.php`

**Interfaces:**
- Consumes: Tasks 3-8.

**This file has `confirm_addtitleline` but NOT `confirm_addsubtotalline`**, despite having the "Add subtotal line" button — that button is a dead end today. This task fixes it, alongside adding the text-line handler.

- [ ] **Step 1: Add the GET action dispatch for text**

Current code (around line 1384):

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

Change to:

```php
	// Subtotal line form
	if ($action == 'add_title_line') {
		$langs->load('subtotals');
		$type = 'title';
		$depth_array = $object->getPossibleLevels($langs);
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_subtotal_line') {
		$langs->load('subtotals');
		$type = 'subtotal';
		$titles = $object->getPossibleTitles();
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	} elseif ($action == 'add_text_line') {
		$langs->load('subtotals');
		$type = 'text';
		require dol_buildpath('/core/tpl/subtotal_create.tpl.php');
	}
```

- [ ] **Step 2: Add the missing `confirm_addsubtotalline` handler and the new `confirm_addtextline` handler**

Current code (around line 669-714):

```php
	} elseif ($action == 'confirm_addtitleline' && $permissiontoadd) {
		// Handling adding a new title line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotallinedesc', 'alphanohtml');
		$depth = GETPOSTINT('subtotallinelevel') ?? 1;

		$subtotal_options = array();

		foreach (Fichinter::$TITLE_OPTIONS as $option) {
			$value = GETPOST($option, 'alphanohtml');
			if ($value) {
				$subtotal_options[$option] = $value == 'on' ? 1 : $value;
			}
		}

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, (int) $depth, $subtotal_options);

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
```

Note the permission variable here is `$permissiontoadd`, not `$usercancreate` (unique to this file among the 8 — verified against `Fichinter::$SUBTOTAL_OPTIONS`, which exists via the `CommonSubtotal` trait exactly like `$TITLE_OPTIONS`). Change to:

```php
	} elseif ($action == 'confirm_addtitleline' && $permissiontoadd) {
		// Handling adding a new title line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotallinedesc', 'alphanohtml');
		$depth = GETPOSTINT('subtotallinelevel') ?? 1;

		$subtotal_options = array();

		foreach (Fichinter::$TITLE_OPTIONS as $option) {
			$value = GETPOST($option, 'alphanohtml');
			if ($value) {
				$subtotal_options[$option] = $value == 'on' ? 1 : $value;
			}
		}

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, (int) $depth, $subtotal_options);

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
	} elseif ($action == 'confirm_addsubtotalline' && $permissiontoadd) {
		// Handling adding a new subtotal line for subtotals module

		$langs->load('subtotals');

		$choosen_line = GETPOST('subtotaltitleline', 'alphanohtml');
		foreach ($object->lines as $line) {
			if ($line->desc == $choosen_line && $line->special_code == SUBTOTALS_SPECIAL_CODE) {
				$desc = $line->desc;
				$depth = -$line->qty;
			}
		}

		$subtotal_options = array();

		foreach (Fichinter::$SUBTOTAL_OPTIONS as $option) {
			$value = GETPOST($option, 'alphanohtml');
			if ($value) {
				$subtotal_options[$option] = $value == 'on' ? 1 : $value;
			}
		}

		// Insert line
		if (isset($desc) && isset($depth)) {
			$result = $object->addSubtotalLine($langs, $desc, (int) $depth, $subtotal_options);
		} else {
			$result = -1;
			$object->errors[] = $langs->trans("CorrespondingTitleNotFound");
		}

		if ($result >= 0) {
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
	} elseif ($action == 'confirm_addtextline' && $permissiontoadd) {
		// Handling adding a new text line for subtotals module

		$langs->load('subtotals');

		$desc = GETPOST('subtotaltextcontent', 'restricthtml');

		// Insert line
		$result = $object->addSubtotalLine($langs, $desc, 0, array());

		if ($result >= 0) {
			if ($result == 0) {
				setEventMessages($object->error, $object->errors, 'warnings');
			}
			$ret = $object->fetch($object->id); // Reload to get new records
			$object->fetch_thirdparty();

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = GETPOST('lang_id', 'alpha');
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
```

- [ ] **Step 3: Add the "Add text line" button, and fix the button-gating condition**

Current code (around line 1897-1918):

```php
				// Subtotal
				if ($object->status == Fichinter::STATUS_DRAFT && isModEnabled('subtotals') && getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element))) {
					$langs->load('subtotals');

					$url_button = array();
					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('intervention') && $object->status == Fichinter::STATUS_DRAFT),
						'perm' => (bool) $permissiontoadd,
						'label' => $langs->trans('AddTitleLine'),
						'url' => '/fichinter/card.php?id='.$object->id.'&action=add_title_line&token='.newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('intervention') && $object->status == Fichinter::STATUS_DRAFT),
						'perm' => (bool) $permissiontoadd,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => '/fichinter/card.php?id='.$object->id.'&action=add_subtotal_line&token='.newToken()
					);
					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
				}
```

Change to:

```php
				// Subtotal
				if ($object->status == Fichinter::STATUS_DRAFT && isModEnabled('subtotals')
					&& (getDolGlobalString('SUBTOTAL_TITLE_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_'.strtoupper($object->element)) || getDolGlobalString('SUBTOTAL_TEXT_'.strtoupper($object->element)))) {
					$langs->load('subtotals');

					$url_button = array();
					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('intervention') && $object->status == Fichinter::STATUS_DRAFT),
						'perm' => (bool) $permissiontoadd,
						'label' => $langs->trans('AddTitleLine'),
						'url' => '/fichinter/card.php?id='.$object->id.'&action=add_title_line&token='.newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('intervention') && $object->status == Fichinter::STATUS_DRAFT),
						'perm' => (bool) $permissiontoadd,
						'label' => $langs->trans('AddSubtotalLine'),
						'url' => '/fichinter/card.php?id='.$object->id.'&action=add_subtotal_line&token='.newToken()
					);

					$url_button[] = array(
						'lang' => 'subtotals',
						'enabled' => (isModEnabled('intervention') && $object->status == Fichinter::STATUS_DRAFT),
						'perm' => (bool) $permissiontoadd,
						'label' => $langs->trans('AddTextLine'),
						'url' => '/fichinter/card.php?id='.$object->id.'&action=add_text_line&token='.newToken()
					);
					print dolGetButtonAction('', $langs->trans('Subtotal'), 'default', $url_button, '', true);
				}
```

- [ ] **Step 4: Run PHP lint**

```bash
cd dolibarr
docker compose exec -T php-fpm php -l /application/dolibarr/htdocs/fichinter/card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Manually verify the subtotal-line fix and the text line**

```bash
mysql -h127.0.0.1 -P3306 -udolibarr_user -pdolibarr_password dolibarr \
  -e "DELETE FROM llx_const WHERE name IN ('SUBTOTAL_TITLE_FICHINTER', 'SUBTOTAL_FICHINTER', 'SUBTOTAL_TEXT_FICHINTER'); INSERT INTO llx_const (name, value, type, entity) VALUES ('SUBTOTAL_TITLE_FICHINTER', '1', 'chaine', 1), ('SUBTOTAL_FICHINTER', '1', 'chaine', 1), ('SUBTOTAL_TEXT_FICHINTER', '1', 'chaine', 1);"
```

On a draft intervention: add a title line, then click "Add subtotal line" on it and submit — confirm it now actually creates the closing subtotal line (previously a dead end). Then run the same text-line test as Task 9's Step 5.

- [ ] **Step 6: Commit**

```bash
cd dolibarr
git add htdocs/fichinter/card.php
git commit -m "FIX: Add missing subtotal line handler and add text-line support to Fichinter"
```

---

### Task 17: Expedition end-to-end verification (no code changes)

**Files:** none — verification only, per the "Corrections to the design spec" section above.

**Interfaces:**
- Consumes: Task 10 (Commande must support text lines for there to be anything to copy).

- [ ] **Step 1: Confirm `SUBTOTAL_TEXT_*` genuinely has no Expedition entry to configure**

```bash
cd dolibarr
grep -n "SHIPPING\|EXPEDITION" htdocs/admin/subtotals.php
```

Expected: no match — confirms (as found during planning) there is no per-type toggle for shipments, matching that title/subtotal/text lines on shipments are inherited from the source order, not independently enabled.

- [ ] **Step 2: Manual end-to-end test**

Using the draft Commande from Task 10's Step 5 (which has a text line on it, with `SUBTOTAL_TEXT_COMMANDE` enabled): validate the order, then create a shipment from it. On the shipment-creation form, confirm the text line appears as a selectable checkbox row (via `subtotalline_select.tpl.php`, unchanged) alongside any title/subtotal lines. Select it, create the shipment. On the resulting shipment's line list, confirm the text line appears (rendered via the same `subtotal_view.tpl.php` Task 8 branch, since `htdocs/expedition/card.php:3742` delegates to `/core/tpl/subtotal_expedition_view.tpl.php` for view mode — if that template has its own `qty`-sign branching separate from `subtotal_view.tpl.php`, note it here as a finding rather than silently patching it, since it is out of this task's "no code changes" scope; if it turns out to need a change, that is a real gap in Task 8 and should be fixed there, re-running Task 8's review).

- [ ] **Step 3: Record the outcome**

If the manual test in Step 2 passes with no code changes, this task is complete as verification-only. If it surfaces that `subtotal_expedition_view.tpl.php` (not audited in this plan — it was not in the list of files needing a new branch during the original investigation) needs its own `qty == 0` branch, treat that as a finding to fix in Task 8's file scope (amend Task 8, not this task), then re-verify here.

- [ ] **Step 4: Commit (only if Step 3 required a fix in Task 8's scope)**

If no code changed, there is nothing to commit for this task — mark it complete based on the manual verification alone.

---

## After all tasks

Update `ChangeLog` with a line describing this feature, per repo convention for significant changes.
