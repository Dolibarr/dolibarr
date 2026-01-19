# IndexAdjustment Module for Dolibarr

Transparent contract index adjustments based on Austrian VPI (Verbraucherpreisindex).

## Features

- **Batch Price Adjustments**: Adjust multiple contract lines at once
- **Customer Selection**: Filter by specific customer or apply to all
- **Preview Mode**: See calculated changes before execution
- **Audit Trail**: Complete before/after price documentation
- **Event Logging**: ActionComm events created per contract
- **Rollback Support**: Revert adjustments within configurable time window
- **CLI Tool**: Command-line interface for automated processing
- **VPI Calculator**: Optional VPI-based percentage calculation

## Requirements

- Dolibarr 16.0+
- PHP 7.4+
- Contract module enabled

## Installation

1. Copy the `indexadjustment` folder to `/htdocs/custom/`
2. Go to Home → Setup → Modules
3. Find "Index Adjustment" in the Financial section
4. Click "Activate"

## Configuration

Go to Home → Setup → Modules → Index Adjustment → Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Default Threshold | 0% | Minimum percentage required |
| Rounding Mode | Standard | How to round calculated prices |
| Rollback Days | 30 | Days allowed for rollback |
| VPI Base Year | 2020 | Default VPI reference year |

## Usage

### Web Interface

1. Go to Commercial → Contracts → Index Adjustments
2. Click "New Index Adjustment"
3. Enter adjustment percentage (e.g., 4.5%)
4. Optionally select a specific customer
5. Click "Preview" to see calculated changes
6. Review the preview and click "Execute"
7. Verify changes in contract cards and events

### CLI Tool

```bash
# Show help
php htdocs/custom/indexadjustment/scripts/indexadjustment_batch.php --help

# Preview adjustment for all customers
php scripts/indexadjustment_batch.php --percent=4.5 --preview

# Execute adjustment for specific customer
php scripts/indexadjustment_batch.php --percent=4.5 --customer=123 --execute
```

## How It Works

1. **Selection**: User selects contracts with active service lines (statut=4)
2. **Calculation**: New prices calculated using configurable rounding
3. **Preview**: Before/after comparison displayed
4. **Execution**: Contract lines updated via `Contrat::updateline()` API
5. **Audit**: `llx_indexadjustment_line` records before/after prices
6. **Events**: ActionComm created per contract with full details

## Permissions

| Permission | Description |
|------------|-------------|
| Read | View index adjustments |
| Write | Create and update adjustments |
| Execute | Run adjustments (update prices) |
| Rollback | Revert executed adjustments |
| Delete | Remove adjustment records |

## Translations

- English (en_US)
- German (de_DE)

## License

GPL-3.0+

## Author

Florian Hödl <florian@hoedl.co>
Anexum GmbH
