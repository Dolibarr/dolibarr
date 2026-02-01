# Error Log (Improvement Loop)

This file tracks errors and mistakes to prevent repetition. Read before starting work.

---

## 2026-01-05 10:08

### Mistake

Used wrong column name `datef` instead of `datep2` for actioncomm end date.

### Symptom

SQL error: `Unknown column 'a.datef' in 'SELECT'` when loading router_agenda.php

### Cause

Assumed Dolibarr's `llx_actioncomm` table uses `datef` for end date (like other tables), but it actually uses `datep2`.

### Fix

Changed `a.datef as df` to `a.datep2 as df` in the SQL query.

### Prevention rule

Always verify Dolibarr table column names with `DESCRIBE llx_tablename` before writing SQL queries. Key actioncomm columns: `datep` (start), `datep2` (end), `datec` (creation).

### Context

`htdocs/custom/anxconnect/router_agenda.php` line 226

---

## 2026-01-25 - Extrafield VARCHAR vs INT comparison

### Mistake

Used integer comparison `(int) $fk_cost_period` for extrafield `propat_cost_period_id` in SQL WHERE clause.

### Symptom

"No record found" when querying linked invoices despite records existing in database.

### Cause

Extrafields in Dolibarr are stored as VARCHAR, not INT. Using `(int)` casts a VARCHAR value like "137" to integer, but the comparison in SQL fails because the database column is VARCHAR.

### Fix

Changed `WHERE fe.propat_cost_period_id = ".(int) $id` to `WHERE fe.propat_cost_period_id = '".$db->escape($id)."'"` (string comparison).

### Prevention rule

Always use string comparison with `$db->escape()` for extrafield values, even if they store numeric data. Extrafields are VARCHAR columns.

### Context

`htdocs/custom/propertymanagementat/class/costperiod.class.php`, `wizard/step2_costs.php`, `class/allocationengine.class.php`, `costperiod/card.php`, `costperiod/invoices.php`

---

## 2026-01-25 - FactureFournisseur status display

### Mistake

Only set `$invoiceStatic->statut` without also setting `$invoiceStatic->status` when displaying invoice status.

### Symptom

Invoice status always displayed as "Draft" even when invoices were validated or paid.

### Cause

Dolibarr 22.0+ uses `status` property for `getLibStatut()`. The old `statut` property is kept for backward compatibility but both need to be set.

### Fix

Added `$invoiceStatic->status = $obj->fk_statut;` after setting `$invoiceStatic->statut`.

### Prevention rule

When populating a static object for display, always set BOTH `statut` and `status` properties from the database `fk_statut` value.

### Context

`htdocs/custom/propertymanagementat/costperiod/card.php`, `wizard/step2_costs.php`

---

## 2026-01-26 - Custom module menu URLs missing /custom/ prefix

### Mistake

Defined menu URLs as `/earechnungat/report.php` instead of `/custom/earechnungat/report.php` in module descriptor.

### Symptom

Module activated but menu entries not visible in Dolibarr interface; clicking would lead to 404.

### Cause

Custom modules live under `htdocs/custom/` but the menu URL was defined relative to `htdocs/`. Dolibarr prepends `DOL_URL_ROOT` to menu URLs, so without `/custom/` the URL resolves to `htdocs/earechnungat/report.php` which doesn't exist.

### Fix

Changed all menu `url` values from `/earechnungat/report.php` to `/custom/earechnungat/report.php`.

### Prevention rule

Custom module menu URLs MUST include the `/custom/` prefix. Always use `/custom/<modulename>/page.php` format for menu definitions in `modXxx.class.php`.

### Context

`htdocs/custom/earechnungat/core/modules/modEARechnungAT.class.php`

---

## 2026-01-27 - Non-existent setStatus() method called on PropatProperty

### Mistake

Called `$object->setStatus(PropatProperty::STATUS_ACTIVE)` in property card.php, but `setStatus()` does not exist on the class.

### Symptom

Clicking "Activate" or "Close" button on a property card does nothing (silent failure, no status change).

### Cause

The `PropatProperty` class follows standard Dolibarr pattern with named status methods (`setValidated()`, `setClosed()`, `setDraft()`) instead of a generic `setStatus()`. The card.php was written calling the wrong method name.

### Fix

Changed `$object->setStatus(PropatProperty::STATUS_ACTIVE)` to `$object->setValidated($user)` and `$object->setStatus(PropatProperty::STATUS_CLOSED)` to `$object->setClosed($user)`.

### Prevention rule

Use the correct Dolibarr status methods: `setValidated($user)`, `setClosed($user)`, `setDraft($user)`. These call `setStatusCommon()` internally. Never call `setStatus()` directly.

### Context

`htdocs/custom/propertymanagementat/property/card.php` lines 207, 221
`htdocs/custom/propertymanagementat/unit/card.php` lines 206 (`validate()` → `setValidated()`), 232 (`close()` → `setClosed()`)

---

## 2026-01-27 - CostPeriod create fails with ErrorRefRequired

### Mistake

`costperiod.class.php` `create()` method rejects empty `ref` before `createCommon()` can apply the `(PROV)` default.

### Symptom

Creating a new cost period fails with "ErrorRefRequired" even though ref is auto-generated.

### Cause

The `ref` field has `'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)'` in the fields definition, meaning it's auto-generated. But the `create()` method had an explicit `if (empty($this->ref))` guard that returned an error before `createCommon()` could apply the default.

### Fix

Changed the guard from returning an error to auto-assigning `$this->ref = '(PROV)'`.

### Prevention rule

For auto-generated ref fields (`visible=4, noteditable=1`), never reject empty ref in `create()`. Instead, assign the provisional `(PROV)` ref so `createCommon()` can process it.

### Context

`htdocs/custom/propertymanagementat/class/costperiod.class.php` line 134

---

## 2026-01-27 - CostPeriod card.php uses wrong property names for dates

### Mistake

Used `$object->date_period_start` / `$object->date_period_end` instead of `$object->date_start` / `$object->date_end`.

### Symptom

Dates not saved to database on create/update; dates not displayed in view mode after fetch.

### Cause

The HTML form elements are named `date_period_start` / `date_period_end`, but the class fields are `date_start` / `date_end`. The card.php confused form element names with object property names.

### Fix

Changed all `$object->date_period_start` to `$object->date_start` and `$object->date_period_end` to `$object->date_end`.

### Prevention rule

Form element names (used by `selectDate()` and `GETPOST()`) are separate from class property names (defined in `$fields` array). Always use the property name from the `$fields` array when setting object properties.

### Context

`htdocs/custom/propertymanagementat/costperiod/card.php` lines 84-85, 97, 101, 148-149, 151, 428, 433, 525, 530

---

## 2026-01-27 - Statement list.php SQL uses non-existent table alias 'l'

### Mistake

SQL query referenced alias `l` (`l.ref`, `l.rowid`) but the contract table was aliased as `c`.

### Symptom

Statement list page shows database error: "Unknown column 'l.ref' in 'SELECT'"

### Cause

Likely a leftover from renaming the alias from `l` (lease) to `c` (contrat) without updating all references.

### Fix

Changed `l.ref` → `c.ref` and `t.fk_contract = l.rowid` → `t.fk_contract = c.rowid`.

### Prevention rule

After renaming SQL table aliases, search for ALL references to the old alias in the query. Use consistent alias naming (e.g., first letter of table name).

### Context

`htdocs/custom/propertymanagementat/statement/list.php` lines 83, 88

---

## 2026-01-27 - PropatStatement missing getNomUrl() and getLibStatut()

### Mistake

`PropatStatement` class had no `getNomUrl()` or `getLibStatut()` methods, but `statement/list.php` called both.

### Symptom

Statement ref not displayed as clickable link in list, preventing navigation to statement card. Status display also broken.

### Cause

Class was generated without the standard Dolibarr display methods that all business objects need.

### Fix

Added `getNomUrl()`, `getLibStatut()`, and `LibStatut()` methods following the same pattern as `PropatProperty`.

### Prevention rule

Every business object class that appears in a list must implement `getNomUrl()`, `getLibStatut()`, and `LibStatut()`. Check for these methods when creating new business objects.

### Context

`htdocs/custom/propertymanagementat/class/statement.class.php`

---

## 2026-01-27 - Statement card.php multiple property name mismatches and broken actions

### Mistake

1. Financial summary used wrong property names (`bk_total`, `heating_total`, `advances_paid`, `balance`)
2. Statement lines used wrong property names (`category_total`, `allocation_percentage`, `tenant_share`)
3. Finalize/deliver actions called `update()` which rejects non-DRAFT status, instead of `finalize()` / `markDelivered()`
4. Deadline calculation used `$costperiod->date_period_end` instead of `$costperiod->date_end`
5. Delivery date used `$object->delivery_date` instead of `$object->date_delivered`

### Fix

- Financial: `bk_total` → `total_bk_share`, `heating_total` → `total_heating_share`, `advances_paid` → `total_bk_advances`, `balance` → `grand_total`
- Lines: `category_total` → `category_total_net`, `allocation_percentage` → `allocation_percent`, `tenant_share` → `tenant_share_net`
- Actions: Used `$object->finalize($user)` and `$object->markDelivered($user, $method)`
- Dates: `date_period_end` → `date_end`, `delivery_date` → `date_delivered`, `date_creation` → `generated_date`

### Prevention rule

Always verify property names against the class `$fields` array before using them in card/list pages. Never assume property names - read the class definition first.

### Context

`htdocs/custom/propertymanagementat/statement/card.php`

---

## 2026-01-27

### Mistake

SQL data files with UTF-8 special characters (ä, ö, ü) loaded without explicit charset declaration.

### Symptom

Holiday names displayed as mojibake: "MariÃ¤ EmpfÃ¤ngnis" instead of "Mariä Empfängnis", "KÃ¶nige" instead of "Könige".

### Cause

SQL data file `llx_doliprom_holidays_data.sql` contained UTF-8 text but was loaded via MySQL CLI with default `latin1` connection charset. MySQL interpreted the UTF-8 bytes as Latin-1, then re-encoded them to UTF-8 for storage, resulting in double-encoded data (`C383C2A4` instead of `C3A4` for ä).

### Fix

1. Fixed existing data: `UPDATE ... SET name = CONVERT(CAST(CONVERT(name USING latin1) AS BINARY) USING utf8mb4)`
2. Added `SET NAMES utf8mb4;` to SQL data files
3. Added `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` to CREATE TABLE statement

### Prevention rule

Always add `SET NAMES utf8mb4;` at the top of SQL files containing non-ASCII characters (German umlauts, etc.). Always specify `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` on CREATE TABLE statements.

### Context

`htdocs/custom/doliprom/sql/llx_doliprom_holidays_data.sql`, `htdocs/custom/doliprom/sql/llx_doliprom_holidays.sql`

---

## 2026-01-31 - Dolibarr trigger file naming convention

### Mistake

Named trigger file `interface_99_modRecurringRevenue_Triggers.class.php` with class `InterfaceRecurringRevenueTriggers`.

### Symptom

Trigger never fired. Debug showed "class InterfaceTriggers was not found" error.

### Cause

Dolibarr expects trigger class name = `Interface` + ucfirst(third part of filename). For filename `interface_99_modRecurringRevenue_Triggers.class.php`, it expects class `InterfaceTriggers`, not `InterfaceRecurringRevenueTriggers`.

### Fix

Renamed file to `interface_99_modRecurringRevenue_RecurringRevenueTriggers.class.php` so Dolibarr expects class `InterfaceRecurringRevenueTriggers`.

### Prevention rule

Trigger filename pattern: `interface_NN_modModuleName_ClassName.class.php` where the class inside must be `InterfaceClassName`. Example: `interface_99_modDoliProm_DoliPromTriggers.class.php` → class `InterfaceDoliPromTriggers`.

### Context

`htdocs/custom/recurringrevenue/core/triggers/`

---

## 2026-01-27

### Mistake

OpenAI API key displayed in plain text in admin setup form.

### Symptom

API key fully visible as `type="text"` input field and in view mode output.

### Cause

Backport `FormSetupItem::generateInputFieldSecureKey()` used `type="text"` instead of `type="password"`. View mode `generateOutputField()` had no handler for `securekey` type, falling through to default which outputs raw value.

### Fix

1. Changed input to `type="password"` with `autocomplete="off"`
2. Added `securekey` case in `generateOutputField()` that masks all but last 4 characters

### Prevention rule

Always use `type="password"` for secret/key input fields. Always handle secret field types in both input and output rendering methods.

### Context

`htdocs/custom/doliprom/backport/v16/core/class/html.formsetup.class.php`

---

## 2026-01-31 15:35

### Mistake

Used non-existent method `$langs->transnoentitiesaliased()` instead of `$langs->transnoentities()`.

### Symptom

Fatal error: `Call to undefined method Translate::transnoentitiesaliased()` in alert.class.php and alertgroup.class.php

### Cause

Typo when writing the LibStatut method - wrote `transnoentitiesaliased` instead of `transnoentities`.

### Fix

Changed all occurrences of `transnoentitiesaliased` to `transnoentities` in:
- `class/alert.class.php`
- `class/alertgroup.class.php`

### Prevention rule

Dolibarr's Translate class methods are: `trans()`, `transnoentities()`, `transnoentitiesnoconv()`. Never use `transnoentitiesaliased` - it doesn't exist.

### Context

DoliProm Alert History feature
