# IndexAdjustment Module - Claude Code Context

This file provides Claude Code with comprehensive context about the **IndexAdjustment** module for Dolibarr.

## Module Overview

**IndexAdjustment** is a custom Dolibarr module for transparent contract index adjustments based on the Austrian VPI (Verbraucherpreisindex / Consumer Price Index).

### Key Information

- **Version**: 1.0.0
- **Author**: Florian Hödl (florian@hoedl.co)
- **Module ID**: 510100
- **License**: GPL-3.0+
- **Location**: `/var/www/html/htdocs/custom/indexadjustment/`

### Business Purpose

IndexAdjustment enables Anexum to perform batch price adjustments on contract service lines with full audit trail:

1. **Batch Adjustments**: Select customers/contracts → Preview changes → Execute
2. **Transparent Documentation**: ActionComm events created per contract
3. **Audit Trail**: Complete before/after price records
4. **Rollback Capability**: Revert adjustments within configurable time window
5. **CLI Support**: Automated batch processing via command line

## Architecture

### Core Workflow

```
Select Customers → Select Contracts → Preview → Execute → Document Events
                                        ↓
                                  Update Contract Lines
                                  via Contrat::updateline()
```

### Key Classes

#### 1. `IndexAdjustment` - Main Business Object
- **Location**: `class/indexadjustment.class.php`
- **Extends**: `CommonObject`
- **Status Constants**:
  - `STATUS_DRAFT = 0`
  - `STATUS_VALIDATED = 1`
  - `STATUS_EXECUTED = 2`
  - `STATUS_CANCELLED = 9`

#### 2. `IndexAdjustmentLine` - Line Records
- **Location**: `class/indexadjustmentline.class.php`
- **Extends**: `CommonObjectLine`
- **Purpose**: Store before/after prices for each adjusted contract line

#### 3. `IndexAdjustmentCalculator` - Price Calculations
- **Location**: `class/indexadjustment_calculator.class.php`
- **Purpose**: Pure calculation logic (stateless)
- **Key Methods**:
  - `calculateAdjustedPrice($price, $percent)` - Apply percentage
  - `calculateVpiAdjustment($base, $current)` - Calculate VPI %
  - `meetsThreshold($percent, $threshold)` - Check minimum threshold
  - `calculateBatch($lines, $percent)` - Process multiple lines

#### 4. `IndexAdjustmentService` - Business Logic
- **Location**: `class/indexadjustment_service.class.php`
- **Purpose**: Orchestrate adjustments
- **Key Methods**:
  - `fetchActiveContracts($customerId)` - Get eligible contracts
  - `fetchActiveServiceLines($contractId)` - Get lines with statut=4
  - `previewAdjustments($contractIds, $percent)` - Calculate preview
  - `execute($adjustment, $user, $contractIds)` - Perform adjustment
  - `rollback($adjustment, $user)` - Revert changes
  - `canRollback($adjustment)` - Check time window

## Database Schema

### Table: `llx_indexadjustment`

```sql
rowid               INTEGER AUTO_INCREMENT PRIMARY KEY
ref                 VARCHAR(128) NOT NULL      -- IA-2024-0001
entity              INTEGER DEFAULT 1
datec               DATETIME NOT NULL
tms                 TIMESTAMP
fk_user_creat       INTEGER NOT NULL
label               VARCHAR(255) NOT NULL
description         TEXT
adjustment_date     DATE NOT NULL
adjustment_percent  DOUBLE(10,4) NOT NULL      -- e.g., 4.5000
vpi_base_year       INTEGER                    -- e.g., 2020
vpi_base_value      DOUBLE(10,2)               -- e.g., 100.00
vpi_current_year    INTEGER                    -- e.g., 2024
vpi_current_value   DOUBLE(10,2)               -- e.g., 104.50
fk_soc              INTEGER                    -- NULL = all customers
status              INTEGER DEFAULT 0          -- 0/1/2/9
date_executed       DATETIME
fk_user_executed    INTEGER
total_contracts     INTEGER DEFAULT 0
total_lines         INTEGER DEFAULT 0
total_ht_before     DOUBLE(24,8) DEFAULT 0
total_ht_after      DOUBLE(24,8) DEFAULT 0
```

### Table: `llx_indexadjustment_line`

```sql
rowid               INTEGER AUTO_INCREMENT PRIMARY KEY
fk_indexadjustment  INTEGER NOT NULL           -- Parent reference
fk_contrat          INTEGER NOT NULL           -- Contract ID
fk_contratdet       INTEGER NOT NULL           -- Contract line ID
product_ref         VARCHAR(128)
product_label       VARCHAR(255)
subprice_before     DOUBLE(24,8) NOT NULL
qty                 DOUBLE(24,8) DEFAULT 1
total_ht_before     DOUBLE(24,8) NOT NULL
subprice_after      DOUBLE(24,8) NOT NULL
total_ht_after      DOUBLE(24,8) NOT NULL
price_diff_ht       DOUBLE(24,8) NOT NULL
rollback_executed   TINYINT DEFAULT 0
rollback_date       DATETIME
fk_user_rollback    INTEGER
```

## Configuration

### Module Constants

| Constant | Type | Default | Description |
|----------|------|---------|-------------|
| `INDEXADJUSTMENT_DEFAULT_THRESHOLD` | string | 0 | Minimum % for adjustment |
| `INDEXADJUSTMENT_ROUNDING_MODE` | string | standard | standard/up/down |
| `INDEXADJUSTMENT_ROLLBACK_DAYS` | string | 30 | Days allowed for rollback |
| `INDEXADJUSTMENT_VPI_BASE_YEAR` | string | 2020 | Default VPI base year |

## Contract Integration

### How Prices Are Updated

The module uses Dolibarr's native `Contrat::updateline()` method:

```php
$contract->updateline(
    $lineId,                    // Line ID
    $description,               // Description
    $newSubprice,               // NEW adjusted price
    $qty,                       // Quantity
    0,                          // Remise percent
    0, 0,                       // Date start/end
    $tva_tx,                    // VAT rate
    0, 0,                       // Planned dates
    'HT',                       // Price base type
    ...
);
```

This ensures:
- Proper total recalculation
- Trigger execution
- Compatibility with contract revenue module
- Event logging

### Only Active Service Lines

The module only adjusts contract lines with `statut = 4` (active running service):

```sql
SELECT * FROM llx_contratdet
WHERE fk_contrat = ?
AND statut = 4  -- Active running service
```

Line status values:
- 0 = Draft
- 4 = Active (running service)
- 5 = Closed

## Event Documentation

When adjusting a contract, an ActionComm event is created:

```
Label: "Indexanpassung IA-2024-0001: +4.50%"

Note:
---
Indexanpassung durchgeführt
Referenz: IA-2024-0001
Datum: 2024-01-15
Anpassung: +4.50%

Angepasste Positionen:
- Internet 100/100: €100.00 → €104.50 (+€4.50)
- VoIP Flatrate: €29.90 → €31.25 (+€1.35)

Gesamt HT: €129.90 → €135.75 (+€5.85)
Ausgeführt von: Florian Hödl
---
```

## Testing

### Unit Tests

```bash
# Run Calculator tests (pure unit tests, no Dolibarr needed)
cd /var/www/html/htdocs/custom/indexadjustment
XDEBUG_MODE=off phpunit test/phpunit/IndexAdjustmentCalculatorTest.php

# Run Service tests (requires Dolibarr database)
XDEBUG_MODE=off phpunit test/phpunit/IndexAdjustmentServiceTest.php
```

### Manual Testing

1. **Activate Module**: Home → Setup → Modules → Financial → Index Adjustment
2. **Configure**: Home → Setup → Modules → Index Adjustment → Settings
3. **Create Adjustment**: Commercial → Contracts → Index Adjustments → New
4. **Execute via CLI**: `php scripts/indexadjustment_batch.php --help`

## Permissions

| Permission | Code | Description |
|------------|------|-------------|
| Read | `indexadjustment->indexadjustment->read` | View adjustments |
| Write | `indexadjustment->indexadjustment->write` | Create/update |
| Execute | `indexadjustment->indexadjustment->execute` | Execute adjustments |
| Rollback | `indexadjustment->indexadjustment->rollback` | Revert adjustments |
| Delete | `indexadjustment->indexadjustment->delete` | Delete adjustments |

## File Structure

```
htdocs/custom/indexadjustment/
├── core/modules/modIndexAdjustment.class.php    # Module descriptor
├── class/
│   ├── indexadjustment.class.php                # Main business object
│   ├── indexadjustmentline.class.php            # Line object
│   ├── indexadjustment_service.class.php        # Business logic
│   └── indexadjustment_calculator.class.php     # Calculations
├── admin/setup.php                              # Configuration page
├── lib/indexadjustment.lib.php                  # Helper functions
├── sql/
│   ├── llx_indexadjustment.sql                  # Main table
│   ├── llx_indexadjustment_line.sql             # Lines table
│   └── llx_indexadjustment_extrafields.sql      # Extrafields
├── scripts/indexadjustment_batch.php            # CLI tool
├── test/phpunit/
│   ├── IndexAdjustmentCalculatorTest.php        # Calculator tests
│   └── IndexAdjustmentServiceTest.php           # Service tests
├── wizard.php                                   # Batch wizard UI
├── card.php                                     # Detail view
├── list.php                                     # List view
├── langs/
│   ├── en_US/indexadjustment.lang
│   └── de_DE/indexadjustment.lang
├── CLAUDE.md                                    # This file
├── README.md                                    # User documentation
└── CHANGELOG.md                                 # Version history
```

## Development Notes

### Adding New Calculation Methods

Add to `IndexAdjustmentCalculator`:

```php
public function newCalculation($params)
{
    // Pure calculation, no side effects
    return $result;
}
```

Then add unit test in `IndexAdjustmentCalculatorTest.php`.

### Modifying Contract Update Logic

The update happens in `IndexAdjustmentService::execute()`:

```php
$contract->updateline(
    $lineId,
    $description,
    $newPrice,  // <-- This is what we change
    ...
);
```

### Adding New Audit Fields

1. Add column to `sql/llx_indexadjustment_line.sql`
2. Update `IndexAdjustmentLine::create()` and `fetch()`
3. Update `IndexAdjustmentService::execute()` to populate

## Legal Reference (Austria)

- **VPI Source**: Statistik Austria
- **Wertsicherung**: WKO guidelines for price adjustment clauses
- **Requirements**:
  - Transparent calculation documentation
  - Support for increases AND decreases
  - Configurable threshold support

## Common Issues

### Issue: Adjustment not applying to all lines
**Cause**: Lines must have `statut = 4` (active running service)
**Solution**: Check line status in contract card

### Issue: Rollback not available
**Cause**: Time window exceeded (default 30 days)
**Solution**: Adjust `INDEXADJUSTMENT_ROLLBACK_DAYS` setting

### Issue: Events not appearing in contract timeline
**Cause**: ActionComm creation failed
**Solution**: Check `dol_syslog` for errors during execution
