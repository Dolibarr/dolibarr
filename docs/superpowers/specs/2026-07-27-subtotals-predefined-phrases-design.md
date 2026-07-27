# Subtotals module — predefined phrases dictionary

Date: 2026-07-27
Module: `subtotals` (core module, `htdocs/subtotals/`, `htdocs/core/modules/modSubtotals.class.php`)

## Problem

When adding a "title" line (section header) on a document (quote, order,
invoice, ...), the user types the description as free text
(`subtotallinedesc` in `subtotal_create.tpl.php`). There is no way to reuse
common wording ("Options", "Prestations", ...) across documents without
retyping it every time.

## Goal

Let an admin define a list of predefined phrases (a standard Dolibarr
dictionary, editable from Setup > Dictionaries) and let users pick one from a
dropdown when creating or editing a title line. The dropdown is a shortcut
that fills the existing free-text field — it does not replace free text
entry.

Only title-line descriptions are affected. Subtotal lines are not (their
description already comes from the corresponding title line, not from free
text).

## Data model

New table `llx_c_subtotals_phrases`:

| column | type | notes |
|---|---|---|
| rowid | integer AUTO_INCREMENT PK | |
| entity | integer DEFAULT 1 NOT NULL | multi-company scoping, per current dictionary conventions (e.g. `c_socialnetworks`) |
| code | varchar(32) | technical code |
| label | varchar(255) | the predefined phrase text |
| active | tinyint DEFAULT 1 NOT NULL | |

Unique index on `(entity, code)`.

Files (following the `-subtotals.` suffix convention used by bundled core
modules such as `recruitment`/`bom`, auto-loaded by
`DolibarrModules::_load_tables()`):

- `htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.sql` — `CREATE TABLE`
- `htdocs/install/mysql/tables/llx_c_subtotals_phrases-subtotals.key.sql` — unique index
- `htdocs/install/mysql/data/llx_c_subtotals_phrases-subtotals.sql` — seed data: a handful of example phrases ("Options", "Prestations", "Fournitures", "Main d'œuvre") using the `__ENTITY__` placeholder

`modSubtotals::init()` must call `$this->_load_tables('/install/mysql/', 'subtotals');` (not currently called — the module has no table-owning files yet) so these files run when the module is enabled.

No entry is added to `install/mysql/migration/*.sql`: like `recruitment`/`bom`, this table belongs to an optional module and is created/updated through the module's own enable lifecycle, not through the always-applied core migration path.

## Dictionary registration

`modSubtotals.class.php` gets a `$this->dictionaries` array modeled exactly on the real, working example in `modIncoterm.class.php` (the only non-template instance in core):

```php
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
```

This makes the dictionary appear automatically under Setup > Dictionaries, editable by an admin, with no dedicated admin screen to write.

## UI / UX

`CommonSubtotal` trait (`htdocs/subtotals/class/commonsubtotal.class.php`) gets a new method:

```php
/**
 * Retrieve active predefined phrases for title lines, ready for a select (label => label).
 *
 * @return array<string,string>
 */
public function getPredefinedPhrases()
```

It queries `llx_c_subtotals_phrases` filtered on `active = 1` and
`entity IN (getEntity('c_subtotals_phrases'))`, sorted by label, returned as
`label => label` (the option value is the label text itself, so selecting it
can be copied straight into the text field).

**`subtotal_create.tpl.php`** (`type == 'title'` branch): a new `select`
formquestion entry ("Predefined phrase") is prepended before the existing
`subtotallinedesc` text field, only when `getPredefinedPhrases()` returns at
least one entry. Its `onchange` copies the selected value into
`#subtotallinedesc`. The text field keeps its current behavior (empty by
default, freely editable, submitted as-is).

**`subtotal_edit.tpl.php`**: same idea, added next to the `#line_desc` input
when `$line_type == 'title'` (not for `'subtotal'`, whose description input
is already read-only). Same `onchange` pattern targeting `#line_desc`.

In both cases the dropdown is purely a client-side convenience — the value
actually submitted and processed is always the text field's value, so
`CommonSubtotal::addSubtotalLine()` / `updateSubtotalLine()` and all
downstream logic are unchanged.

## Translations

New keys added to `langs/en_US/subtotals.lang` and `langs/fr_FR/subtotals.lang`:

- `SubtotalsPredefinedPhrases` — dictionary tab name ("Subtotals predefined phrases" / "Phrases prédéfinies de sous-totaux")
- `PredefinedPhrase` — select field label ("Predefined phrase" / "Phrase prédéfinie")

## Testing

No existing PHPUnit suite covers this module (no `SubtotalsTest.php` registered in `AllTests.php`), so this is verified manually:

1. Disable/re-enable the `subtotals` module and confirm `llx_c_subtotals_phrases` is created with the seeded example rows.
2. In Setup > Dictionaries, find "Subtotals predefined phrases", add/edit/deactivate an entry.
3. On a quote (or any supported document type), add a title line: confirm the predefined-phrase dropdown appears, selecting an entry fills the description field, and the field remains editable/overridable.
4. Edit an existing title line inline: confirm the same dropdown behavior.
5. Confirm subtotal lines are unaffected (no dropdown, description still read-only, sourced from the corresponding title).
