# EARechnungAT Module

Einnahmen-Ausgaben-Rechnung (income/expense report) for Austrian companies under 700k EUR annual revenue.

## Module Info

- **Version**: 1.0.0
- **Module ID**: 510200
- **Location**: `/var/www/html/htdocs/custom/earechnungat/`

## What It Does

Generates an E/A-Rechnung by aggregating data from existing Dolibarr tables. No new database tables are created. Supports both Ist-Besteuerung (payment date) and Soll-Besteuerung (invoice date).

## Data Sources

| Source | Tables | Purpose |
|--------|--------|---------|
| Customer invoices | `llx_facture` + `llx_paiement` + `llx_paiement_facture` | Income by VAT rate |
| Supplier invoices | `llx_facture_fourn` + `llx_paiementfourn` + `llx_paiementfourn_facturefourn` | Expenses by VAT rate |
| Salaries | `llx_payment_salary` | Personnel costs |
| Social charges | `llx_paiementcharge` + `llx_chargesociales` + `llx_c_chargesociales` | Social contributions by type |
| Misc payments | `llx_payment_various` | Other income/expenses |

## Key SQL Logic

For Ist-Besteuerung, payments are proportionally allocated across invoice lines:
```sql
SUM(d.total_ht * ABS(pf.amount) / f.total_ttc)
```

## Files

- `core/modules/modEARechnungAT.class.php` - Module descriptor
- `class/ear_calculator.class.php` - Data aggregation (all SQL queries)
- `lib/earechnungat.lib.php` - Tab helpers, date range utility
- `report.php` - Main report page with filters and CSV export
- `admin/setup.php` - Tax mode and fiscal year configuration
- `admin/about.php` - About page

## Configuration Constants

| Constant | Default | Description |
|----------|---------|-------------|
| `EARECHNUNGAT_TAX_MODE` | `payment` | `payment` (Ist) or `invoice` (Soll) |
| `EARECHNUNGAT_FISCAL_YEAR_START` | `01-01` | Fiscal year start (MM-DD) |

## Permissions

- `earechnungat->report->read` - View reports
- `earechnungat->report->export` - Export CSV

## Testing

```bash
# Syntax check
php -l htdocs/custom/earechnungat/class/ear_calculator.class.php

# Code style
dolibarr-dev cs-check htdocs/custom/earechnungat/

# Static analysis
dolibarr-dev stan htdocs/custom/earechnungat/
```
