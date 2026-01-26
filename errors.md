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
