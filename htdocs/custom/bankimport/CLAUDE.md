# BankImport Module

## Overview

**BankImport** is a Dolibarr financial module that automates bank account reconciliation by importing CSV bank statement files and automatically matching bank transactions with existing Dolibarr records.

- **Version**: 2.9.0
- **Module ID**: 104020
- **Category**: Financial
- **Developer**: ATM Consulting
- **License**: GNU General Public License v3+
- **Compatibility**: Dolibarr 16.0+
- **PHP Requirement**: 7.0+

## Purpose & Key Features

### Core Functionality

1. **Automatic Bank Reconciliation**: Import CSV bank statement files and automatically match transactions
2. **Transaction Matching**: Find corresponding Dolibarr bank entries by amount, label, or check receipt
3. **Missing Transaction Creation**: Create new bank transactions when they don't exist in Dolibarr
4. **Flexible CSV Parsing**: Configurable separators, date formats, and column mappings
5. **Check Receipt Integration**: Match check receipts (bordereau) to bank entries
6. **Import History**: Track all imports with original file data preservation
7. **Multi-format Support**: Handle CSV files with various encodings and line ending formats (Windows, Unix, Mac)
8. **Automatic Discount Creation**: Optional auto-conversion of down payments to reductions when paid
9. **Multi-language Support**: English, French, Spanish (Catalan), German

### Advanced Options

- Match bank lines by amount AND label (configurable)
- Allow reconciliation of draft invoices (with auto-validation)
- Allow payments from multiple third parties in single import
- Create free/unlinked bank entries (not tied to any payment)
- Mac line-ending compatibility mode

## Architecture

### Directory Structure

```
htdocs/custom/bankimport/
├── admin/                              # Module administration pages
│   ├── bankimport_about.php           # About page (credits, links)
│   └── bankimport_setup.php           # Configuration page for constants
├── class/                              # Business logic classes
│   ├── bankimport.class.php           # Main import logic (BankImport class)
│   └── techatm.class.php              # ATM Consulting utility class
├── core/
│   └── modules/
│       └── modBankImport.class.php    # Module descriptor (activation, constants, permissions)
├── langs/                              # Translation files
│   ├── en_US/bankimport.lang
│   ├── fr_FR/bankimport.lang
│   ├── es_ES/bankimport.lang
│   ├── ca_ES/bankimport.lang
│   └── de_DE/bankimport.lang
├── lib/
│   └── bankimport.lib.php             # Library functions (UI helpers)
├── tpl/                                # Template files
│   ├── bankimport.new.tpl.php        # File upload & initial form
│   ├── bankimport.check.tpl.php      # Transaction review/confirmation
│   └── bankimport.end.tpl.php        # Results summary
├── sample/                             # Sample CSV files for testing
├── script/
│   ├── interface.php                  # Database utility interface
│   └── create-maj-base.php            # Database schema creation
├── config.php                          # Configuration loader
├── config.default.php                  # Default config with Abricot dependency
├── import.php                          # Main import entry point (UI)
├── releve.php                          # Statement history viewer
└── README.md / ChangeLog.md / COPYING  # Documentation & license
```

### Key Classes

#### BankImport (class/bankimport.class.php)

**Purpose**: Main import orchestrator handling CSV parsing, transaction matching, and reconciliation logic.

**Key Properties**:
- `$db` - Database handler
- `$account` - Account object (Account class)
- `$file` - Path to CSV file
- `$dateStart`, `$dateEnd` - Date range for reconciliation
- `$numReleve` - Statement number
- `$TBank` - Array of existing Dolibarr bank lines (AccountLine objects)
- `$TCheckReceipt` - Array of check receipts (RemiseCheque objects)
- `$TFile` - Array of parsed CSV transactions
- `$TOriginLine` - Original CSV data (for history preservation)
- `$nbCreated` - Count of newly created transactions
- `$nbReconciled` - Count of reconciled transactions

**Key Methods**:

1. **`analyse($accountId, $filename, $dateStart, $dateEnd, $numReleve, $hasHeader)`**
   - Validates account selection and loads CSV file
   - Returns: boolean (success/failure)

2. **`load_transactions($delimiter, $dateFormat, $mapping_string, $enclosure)`**
   - Orchestrates loading of bank transactions and CSV parsing
   - Triggers hook: `bankimport:load_transactions` (doActions)

3. **`load_bank_transactions()`**
   - Fetches all Dolibarr bank lines for date range/account
   - Populates `$this->TBank` array

4. **`load_file_transactions($delimiter, $dateFormat, $mapping_string, $enclosure)`**
   - Parses CSV file according to configuration
   - Handles amount normalization (converts debit/credit to signed amounts)
   - Converts date strings to timestamps
   - Triggers hook: `bankimport:load_file_transactions` (doActions)
   - Returns: CSV data in `$this->TFile` array

5. **`compare_transactions()`**
   - Matches file transactions to Dolibarr records
   - Primary: Match by amount and optional label
   - Secondary: Match by check receipt total
   - Populates `$fileLine['bankline']` with matching Dolibarr record

6. **`import_data($TLine)`**
   - Executes the actual import after user confirmation
   - Creates missing bank transactions
   - Reconciles matched transactions
   - Handles payment creation (invoices, supplier invoices, charges)
   - Inserts import history if enabled

7. **`create_bank_transaction($fileLine, $oper)`**
   - Creates new bank account line
   - Returns: bankLineId (integer)

8. **`reconcile_bank_transaction($bankLine, $fileLine)`**
   - Sets reconciliation (num_releve) on bank line
   - Updates value date (datev) if needed
   - Increments `$nbReconciled` counter

**Helper Methods**:
- `construct_data_tab_column_file()` - Parse fixed-width column format
- `get_bankline_data()` - Format bank line for display (includes related items)
- `parseHeader()` / `parseLine()` - Parse CSV header/data for history
- `extractNegDir()` - Extract direction token for amount sign determination
- `search_dolibarr_transaction_by_amount()` - Match by amount (with optional date-proximity matching)
- `search_dolibarr_transaction_by_receipt()` - Match by check receipt
- `validateInvoices()` - Auto-validate draft invoices if option enabled
- `doPayment()` - Create payment records and link to bank lines
- `createDiscount()` - Auto-create discounts for paid down payments

#### TBankImportHistory (class/bankimport.class.php)

**Purpose**: Simple data wrapper for storing import history.

**Database Table**: `llx_bankimport_history`

**Fields**:
- `num_releve` - Statement number
- `fk_bank` - Foreign key to bank line
- `line_imported_title` - Header row from original CSV
- `line_imported_value` - Data row from original CSV

## Database

### Tables

**llx_bankimport_history** (created during module initialization)
```
rowid (INT PK AUTO_INCREMENT)
num_releve (VARCHAR 50, indexed)
fk_bank (INTEGER, indexed)
line_imported_title (ARRAY/JSON)
line_imported_value (ARRAY/JSON)
datec (TIMESTAMP)
tms (TIMESTAMP)
```

### Data Dependencies

- **Bank Accounts** (`llx_bank_account`) - Account to import into
- **Bank Lines** (`llx_bank`) - Existing transactions for matching
- **Check Receipts** (`llx_remisecheque`) - For check receipt matching
- **Payments** (`llx_paiement`, `llx_paiementfourn`) - Created during import
- **Invoices** (`llx_facture`) - For payment reconciliation
- **Supplier Invoices** (`llx_facture_fourn`) - For supplier payment reconciliation
- **Social Charges** (`llx_chargesociales`) - For social contribution payments

## Module Configuration

### Module Descriptor (modBankImport.class.php)

**Module Settings**:
- Numero: 104020
- Family: financial
- Special: 2 (other/very specific modules)
- Picto: module.svg@bankimport

**Menu Entry**:
- Left menu under Banking: "Bank import" (/bankimport/import.php)
- Permission check: `$user->rights->bankimport->read`

**Tab Integration**:
- Adds "Account Statements" tab to Bank Account view (when history enabled)
- Conditional: `isModEnabled('bankimport') && getDolGlobalString("BANKIMPORT_HISTORY_IMPORT")`

**Module Constants** (set during initialization):

| Constant | Type | Default | Description |
|----------|------|---------|-------------|
| `BANKIMPORT_MAPPING` | string | `date;label;debit;credit` | CSV column mapping |
| `BANKIMPORT_SEPARATOR` | string | `;` | CSV field separator |
| `BANKIMPORT_DATE_FORMAT` | string | `d/m/Y` | Date format in CSV (%%d, %%m, %%Y) |
| `BANKIMPORT_HEADER` | bool | true | CSV file has header row |

**Optional Constants** (user-configurable):
- `BANKIMPORT_MAC_COMPATIBILITY` - Enable Mac line-ending support
- `BANKIMPORT_HISTORY_IMPORT` - Store import history
- `BANKIMPORT_ALLOW_INVOICE_FROM_SEVERAL_THIRD` - Allow multi-company payments
- `BANKIMPORT_ALLOW_DRAFT_INVOICE` - Auto-validate draft invoices
- `BANKIMPORT_MATCH_BANKLINES_BY_AMOUNT_AND_LABEL` - Match by amount AND label
- `BANKIMPORT_USE_DATE_PROXIMITY` - Use date-proximity matching (best date match instead of first match)
- `BANKIMPORT_DATE_TOLERANCE_DAYS` - Max days deviation for date-proximity matching (0 = no limit)
- `BANKIMPORT_AUTO_CREATE_DISCOUNT` - Auto-convert down payments to discounts

**Permissions**:
- 104021: `bankimport->read` - Access bank import functionality

## User Interface Flow

### Import Process (import.php + templates)

**Step 1: File Upload (bankimport.new.tpl.php)**
```
User selects:
- Bank account (required)
- CSV file (required)
- Date range (start/end)
- Statement number (num_releve)
- Has header line? (yes/no)
```

**Step 2: Configuration & Preview (bankimport.new.tpl.php)**
```
User can override:
- CSV separator (default from BANKIMPORT_SEPARATOR)
- Date format (default from BANKIMPORT_DATE_FORMAT)
- Column mapping (default from BANKIMPORT_MAPPING)
```

**Step 3: Transaction Review (bankimport.check.tpl.php)**
```
Displays side-by-side comparison:
- CSV transactions (left)
- Dolibarr matches (right)
- Planned actions (create/reconcile/skip)
- User selects which transactions to process
- For unmatched: User selects company & payment type
```

**Step 4: Confirmation & Import (bankimport.end.tpl.php)**
```
Results summary:
- X transactions reconciled
- Y transactions created
- Link to statement detail
```

### Statement History View (releve.php)

**Displays**:
- List of statements for bank account
- Transactions per statement with imported data (if history enabled)
- Links to original bank lines and related documents
- Reconciliation information

## Hook Integration

**Hook Contexts**: `bankimport`

**Implementation File**: `import.php`

**Hook Points**:

1. **`load_transactions:doActions`**
   - Triggered before loading transactions
   - Allows custom transaction source/override
   - Return: `>0` (skip standard loading), `0` (continue)

2. **`load_file_transactions:doActions`**
   - Triggered before parsing CSV file
   - Allows custom CSV format parsing
   - Return: `>0` (skip standard parsing), `0` (continue)

3. **`index:doActions` (on form display)**
   - Generic hook at page initialization
   - Parameters: `moduleName`, `tpl`

## Configuration Details

### CSV Mapping Format

**Standard Format** (comma or configurable separator):
```
date;label;debit;credit
date;label;amount
date;label;amount;direction
```

**Column Codes**:
- `date` - Transaction date (required)
- `label` - Transaction description (required)
- `debit` - Debit amount (if present, implies credit column too)
- `credit` - Credit amount (if present, implies debit column too)
- `amount` - Single amount column (with optional `direction` modifier)
- `direction=TOKEN` - Sign indicator (e.g., `direction=DEBIT`, `direction=-`)
- `null` - Ignore column

**Examples**:
```
French bank: date;libelle;debit;credit
European bank: date;description;amount;direction=DEBIT
Amount column: date;label;amount
```

### Date Format Codes

Uses PHP `DateTime::createFromFormat()` syntax:
- `d` - Day (01-31)
- `m` - Month (01-12)
- `Y` - Year (4-digit)
- Examples: `d/m/Y` (25/12/2023), `m-d-Y` (12-25-2023)

### Amount Normalization

- Debits stored as negative values
- Credits stored as positive values
- Converts to `price2num()` format for consistency

## Integration Points

### With Bank Module (`htdocs/compta/bank/`)

- Uses `Account` class for account operations
- Uses `AccountLine` class for transaction records
- Uses `RemiseCheque` class for check receipt matching
- Reconciliation via `num_releve` field on bank lines

### With Payment Modules

- **Customer Payments**: Creates `Paiement` records linked to invoices
- **Supplier Payments**: Creates `PaiementFourn` records linked to supplier invoices
- **Social Charges**: Creates `PaymentSocialContribution` records
- All payments linked to bank line via `addPaymentToBank()`

### With Discount System

- Auto-creates `DiscountAbsolute` records for paid down payments
- Requires option: `BANKIMPORT_AUTO_CREATE_DISCOUNT`

## Common Workflows

### Basic Import Workflow

1. Enable module in Home → Setup → Modules
2. Configure in Home → Setup → Bank Import:
   - Set CSV separator (comma, semicolon, tab)
   - Set date format (dd/mm/yyyy, mm/dd/yyyy, etc.)
   - Set column mapping (date;label;debit;credit)
   - Optional: Enable history tracking
3. Go to Banking → Bank import
4. Select bank account
5. Upload CSV file
6. Review transaction matches
7. Select action for unmatched transactions
8. Confirm import
9. View results

### Handling Unmatched Transactions

For each unmatched transaction, system shows:
- **Create new**: Creates bank line + reconciliation
- **Reconcile**: Matches to existing bank line
- **Create & Link Payment**: Creates bank line AND payment to selected invoice

### CSV File Examples

**Example 1: Debit/Credit Columns**
```
date;label;debit;credit
2025-10-01;Office Supplies;;;5.00
2025-10-02;Customer Payment;500.00;
```

**Example 2: Single Amount Column**
```
date;label;amount;direction=DEBIT
2025-10-01;Office Supplies;5.00;DEBIT
2025-10-02;Customer Payment;500.00;CREDIT
```

## Recent Changes (v2.8 Series)

- **v2.8.3** (2025-10-02): Compatibility with Dolibarr v22
- **v2.8.2** (2025-08-12): Fixed echoing select_comptes
- **v2.8.1** (2025-06-26): Fixed dependencies
- **v2.8.0** (2025-06-17): Added German language support

### Version History

- **v2.7.1** (2025-01-07): Compatibility with v21
- **v2.7.0** (2024-08-04): Compatibility range 16 min - 20 max
- **v2.6.3** (2024-07-11): Fixed undefined variable
- **v2.6.2** (2024-05-24): Fixed double date conversion
- **v2.6.0** (2023-12-07): Added TechATM + About page + logo + hook support
- **v2.4.0** (2023-05-03): Hook system introduced
- **v2.2+**: V13-V22 compatibility
- **v1.0** (2013-09-16): Initial release with auto-reconciliation

## Technical Details

### Dependencies

**Required**:
- Dolibarr 16.0+
- PHP 7.0+
- Bank module (built-in to Dolibarr)

**Optional**:
- Abricot library (custom database abstraction) - referenced but may be optional

### Security

- Permission checks: `$user->rights->bankimport->read` required for access
- Bank account access restricted by `restrictedArea()`
- CSRF tokens via `newToken()`
- SQL injection prevention via parameterized values
- File upload validation and sanitization

### Performance

- Single SQL query per date range for bank transactions
- Single CSV file parse
- Bulk match operation (memory-based)
- Index on `bankimport_history.num_releve` and `fk_bank`

## Troubleshooting

### Common Issues

1. **"File doesn't match mapping"**
   - Check separator character matches CSV format
   - Verify date format matches actual dates in file
   - Ensure column count matches mapping

2. **No transactions matched**
   - Verify amount values are formatted correctly
   - Check date range includes target transactions
   - Consider enabling "match by label" option

3. **Import hangs or times out**
   - Large files: Check server memory limits
   - Many transactions: Consider importing in smaller batches
   - Date range too large: Reduce date range per import

### Debug Tips

- Check Dolibarr logs: Home → Tools → System information → Event logs
- Enable history to verify CSV parsing
- Manual SQL queries on `llx_bank` and `llx_bankimport_history`
- Verify CSV file encoding (UTF-8 recommended)

## API & Extensibility

### Hooks for Customization

Custom modules can implement these hooks:

```php
// In actions_mymodule.class.php
public function doActions($parameters, &$object, &$action, $hookmanager)
{
    if ($action == 'load_transactions') {
        // Custom transaction loading
        return 1; // Skip standard loading
    }
    if ($action == 'load_file_transactions') {
        // Custom CSV parsing
        return 1; // Skip standard parsing
    }
    return 0;
}
```

### Important Notes

- Module uses Abricot database library (ATM internal framework)
- Import logic is self-contained in `BankImport` class
- Extensible via hook system for custom workflows
- Currently no REST API for imports (process via UI)

## Resources

- **Developer**: ATM Consulting (support@atm-consulting.fr)
- **Wiki**: http://wiki.atm-consulting.fr/
- **DoliStore**: Module available on marketplace
- **GitHub**: Module may be hosted at ATM Consulting repositories
- **Support**: contact@atm-consulting.fr or +33 9 77 19 50 70

## License

GNU General Public License v3.0 (COPYING file included)
