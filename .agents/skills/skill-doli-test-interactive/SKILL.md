---
name: skill-doli-test-interactive
description:
  Create interactive PHP test case scripts for Dolibarr ERP/CRM that allow users to setup test data, 
  view results via direct links, and tear down (clean up) the data.
  Use when asked to create test data for Dolibarr issues or when users need to verify bug fixes in the web interface.
triggers:
  - create test case
  - test data
  - setup test
  - dolibarr test
  - test script
  - tear down
  - clean up test
  - interactive test
---

# Dolibarr Interactive Test Skill

## When to Use

Use this skill when you need to create a PHP script that:
- Sets up test data in Dolibarr database
- Provides direct links to view the results in the web interface
- Allows users to tear down/clean up the test data
- Uses Dolibarr's authentication and session system

## Quick Start

1. Read the Dolibarr issue to understand what data needs to be created
2. Create a PHP script in `htdocs/` directory
3. Include proper authentication with `main.inc.php`
4. Add action parameter handling: `create` (default) and `teardown`
5. Create test data with unique identifiers (use timestamps)
6. Output direct links with `target="_blank"` to view data
7. Add teardown logic that deletes in reverse order of creation

## Required Files

**IMPORTANT**: Always include all necessary Dolibarr files. The functions you use may be in different libraries.

**Tip**: To find which files defines the Dolibarr functions or classes, use:
```bash
git grep -P --name-only '(function (GETPOST|dol_escape_htmltag)\b|class (Facture|Societe)\b)' :htdocs/*.php
```

**IMPORTANT**: Always grep for ALL functions you use in your script, including:
- Dolibarr functions (GETPOST, dol_escape_htmltag, etc.)
- PHP core functions that might require extensions (cal_days_in_month requires calendar extension)
- Class names (Facture, Societe, DiscountAbsolute, etc.)

**Best Practice**: Always use `DOL_DOCUMENT_ROOT` constant for require statements to ensure proper path resolution:

```php
require_once 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';  // For GETPOST(), dol_escape_htmltag(), getEntity(), etc.
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';  // For Paiement class
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';  // For Account class
```

**Note**: Even if `main.inc.php` includes some libraries, explicitly require the files where the functions/classes you use are defined to avoid "function not found" or "class not found" errors.

## Access Control

```php
// Access control - restricted to users with permission
if (!$user->rights->facture->lire) {
    accessforbidden();
}
```

## CSRF Protection

**IMPORTANT**: Dolibarr requires CSRF tokens for GET requests with action parameters. Always add `&token=.newToken()` to any link that has an `action` parameter in the query string.

```php
// Correct: Include token in action links
print '<a href="?action=teardown&token='.newToken().'" class="butAction" onclick="return confirm(\'Are you sure?\');">Delete Test Data</a>';

// Correct: For links without action parameter, no token needed
print '<a href="?socid='.$socid.'" class="butAction" target="_blank">View Company</a>';
```

**Note**: The `newToken()` function is available after including `main.inc.php`. Without the token, Dolibarr will refuse the request with: "Access to this page this way (POST method or GET with a sensible value for 'action' parameter) is refused by CSRF protection in main.inc.php. Token not provided."

## Database Queries

**IMPORTANT**: When fetching multiple records, use direct SQL queries with `$db->query()` instead of relying on class methods like `fetch_all()` or `fetchAll()`, which may not exist on all Dolibarr classes.

```php
// Correct: Use direct SQL query
$sql = "SELECT rowid, code_client FROM ".MAIN_DB_PREFIX."societe WHERE code_client LIKE '" .$db->escape($pattern)."' ORDER BY rowid";
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $socids[] = $obj->rowid;
    }
}

// Incorrect: Using non-existent method
$soc = new Societe($db);
$res = $soc->fetch_all('','','','','code_client LIKE \''.$db->escape($pattern).'\'');  // May fail!
```

**Tip**: Always use `MAIN_DB_PREFIX` for table names and `DOL_DOCUMENT_ROOT` for file paths to ensure compatibility across different Dolibarr installations.

**Note on Unique Patterns**: When searching for test data, use a pattern like `'TESTCASE_%'` and handle multiple results. Previous test runs may have created data with the same prefix but different timestamps (e.g., `TESTCASE_1234567890`, `TESTCASE_1234567891`). Always collect all matching IDs and process them in reverse order of creation (newest first) or in a single batch.

**VAT on Services vs Products**: In Dolibarr, VAT treatment differs:
- **Products**: VAT is due on the invoice date
- **Services**: VAT is due on the payment date (when using payment-based VAT calculation)

When testing VAT issues involving discounts/credit notes, use **service lines** (type=1 in addline()) to properly test payment-based VAT calculation.

**Note on delete() method signatures**: Different Dolibarr classes have different delete method signatures:
- `Societe::delete($id, $user)` - Requires ID as first parameter, then user
- `Facture::delete($user)` - Takes user object directly
- `DiscountAbsolute::delete($user)` - Takes user object directly
- `Paiement::delete($user)` - Takes user object directly

Always check the class documentation or source code for the correct delete method signature.

**IMPORTANT**: Most Dolibarr class delete methods require the object to be loaded first before calling delete. You MUST call `fetch()` before `delete()`:

```php
// Correct: Fetch object first, then delete
$inv = new Facture($db);
$inv->fetch($invoice_id);
$result = $inv->delete($user);

$d = new DiscountAbsolute($db);
$d->fetch($discount_id);
$result = $d->delete($user);

$p = new Paiement($db);
$p->fetch($payment_id);
$result = $p->delete($user);

// Incorrect: Calling delete without fetching first will fail
// because delete() methods typically use object properties like $this->fk_facture_source
$inv = new Facture($db);
$result = $inv->delete($user);  // ERROR: Object not loaded
```

The only exception is Societe::delete() which takes the ID as a parameter: `$soc->delete($socid, $user)`.

Also, always check the return value and report errors:
```php
$result = $inv->fetch($invoice_id);
if ($result > 0) {
    $result = $inv->delete($user);
    if ($result > 0) {
        // Success
    } else {
        $error_msg = $inv->error ? dol_escape_htmltag($inv->error) : '(no error message)';
        print '<p class="warning">Failed: '.$error_msg.'</p>';
    }
} else {
    // Fetch failed
    $result = -1;
}
```

**Note on foreign key dependencies**: When deleting invoices/credit notes, ensure all related records (like discounts in `societe_remise_except`) are deleted first. Always search for dependent records across all invoice/credit note IDs, not just a subset.

**Deletion Order**: Always delete in reverse order of creation. For invoices and credit notes, sort by the numeric part of the ref field (not by rowid) to ensure last invoice/credit note number is deleted first. Invoice references may have prefixes (e.g., "FACT-001", "CN-001") but share the same numbering sequence.

```php
// Correct: Sort by numeric part of ref using usort
$all_invoices = array();
$sql = "SELECT rowid, type, ref FROM ".MAIN_DB_PREFIX."facture WHERE fk_soc IN (" .$socid_list.")";
$resql = $db->query($sql);
while ($obj = $db->fetch_object($resql)) {
    $all_invoices[] = $obj;
}
// Sort by numeric part of ref in descending order (like sort -n in bash)
usort($all_invoices, function($a, $b) {
    $numA = preg_replace('/[^0-9]/', '', $a->ref);
    $numB = preg_replace('/[^0-9]/', '', $b->ref);
    return (int)$numB - (int)$numA; // DESC order
});

// Incorrect: String sort on ref may not match numeric order
// (e.g., "FACT-10" < "FACT-2" in string comparison)
usort($all_invoices, function($a, $b) {
    return strcmp($b->ref, $a->ref);
});
```

## Basic Structure

```php
<?php
require_once 'main.inc.php';
// ... other requires

$action = GETPOST('action', 'aZ09');

llxHeader('', 'Test Case Title');

if ($action == 'teardown') {
    // Teardown logic
} else {
    // Create logic (default)
}

llxFooter();
```

## Creating Test Data

### Always Use Unique Identifiers

```php
$timestamp = time();
$soc_code = 'TESTCASE_'.$timestamp;
$soc->name = 'Test Company - '.$timestamp;
$soc->code_client = $soc_code;
```

### Create Company

```php
$soc = new Societe($db);
$soc->name = 'Test Company for Issue #XXXX';
$soc->client = 1;
$soc->fournisseur = 0;
$soc->code_client = 'TESTXXXX_'.time();
$res = $soc->create($user);
```

### Create Invoices

```php
$invoice = new Facture($db);
$invoice->socid = $socid;
$invoice->date = dol_now();
$invoice->type = Facture::TYPE_STANDARD;
$res = $invoice->create($user);
$invoice->addline('Product', 100, 1, 19.6, 0, 0, 0, 0, 0);
$res = $invoice->validate($user);
```

### Create Credit Notes

```php
$creditnote = new Facture($db);
$creditnote->socid = $socid;
$creditnote->type = Facture::TYPE_CREDIT_NOTE;
$creditnote->fk_facture_source = $invoiceid;
// ... add lines, validate
```

### Create Payments/Discounts

```php
$discount = new DiscountAbsolute($db);
$discount->socid = $socid;
$discount->fk_facture = $invoiceid;
$discount->fk_facture_source = $creditnoteid;
$discount->amount_ht = 100.00;
$discount->amount_tva = 19.60;
$discount->amount_ttc = 119.60;
$discount->tva_tx = 19.6;
$discount->description = '(CREDIT_NOTE)';
$discount->discount_type = 0;
$res = $discount->create($user);
```

**Note on Account class**: The `Account` class (bank account) does NOT have a `get_list_of_accounts()` method. Instead, use a direct SQL query:

```php
// Correct: Use direct SQL to find first open bank account
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
$first_account_id = 0;
$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "bank_account WHERE entity IN (" . getEntity('bank_account') . ") AND clos = 0 ORDER BY rowid ASC LIMIT 1";
$resql = $db->query($sql);
if ($resql && $db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
    $first_account_id = $obj->rowid;
}

// Then create payment with proper invoice linking
$payment = new Paiement($db);
$payment->datepaye = dol_now();  // Use datepaye, not datep
$payment->amount = 50.00;
$payment->amounts = array($invoiceid => 50.00);  // REQUIRED: Link payment to invoice(s)
$payment->facid = $invoiceid;  // Optional: for backward compatibility
$payment->socid = $socid;
$payment->fk_account = $first_account_id;  // REQUIRED: Bank account
$payment->paiementid = $payment_mode_id;  // REQUIRED: Payment mode from llx_c_paiement

// First create the payment record
$res = $payment->create($user);
if ($res > 0) {
    // Then add the bank line (optional but recommended)
    $result = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $first_account_id, '', '');
}
```

**Important**: The Paiement class requires:
- `fk_account` field to be set to a valid bank account rowid
- `amounts` array to be set with invoice IDs as keys and amounts as values (e.g., `array($invoiceid => $amount)`)
- The `amount` field for the total payment amount
- `paiementid` field to be set to a valid payment mode ID from `llx_c_paiement` table

To get the first active payment mode (both ID and code):

**Note**: The `llx_c_paiement` table uses `id` as primary key, not `rowid`.

```php
$payment_mode_id = 0;
$payment_mode_code = '';
// Try with entity filter first
$sql = "SELECT id, code FROM " . MAIN_DB_PREFIX . "c_paiement WHERE entity IN (" . getEntity('c_paiement') . ") AND active = 1 ORDER BY id ASC LIMIT 1";
$resql = $db->query($sql);
if (!$resql) {
    // Handle error
} elseif ($db->num_rows($resql) == 0) {
    // Fallback: try without entity filter
    $sql = "SELECT id, code FROM " . MAIN_DB_PREFIX . "c_paiement WHERE active = 1 ORDER BY id ASC LIMIT 1";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $payment_mode_id = $obj->id;
        $payment_mode_code = $obj->code;
    }
} elseif ($db->num_rows($resql) > 0) {
    $obj = $db->fetch_object($resql);
    $payment_mode_id = $obj->id;
    $payment_mode_code = $obj->code;
}
if ($payment_mode_id <= 0 || empty($payment_mode_code)) {
    // Cannot create payment without valid payment mode
}
```
**Important**: Set BOTH `paiementid` and `paiementcode` on the Paiement object. The `addPaymentToBank()` method passes the payment code to `Account::addline()`, which requires a valid code (not numeric ID). If only the ID is set, it will fail with "Attempt to read property 'code' on null".

After creating the payment with `$payment->create($user)`, optionally call `$payment->addPaymentToBank()` to create the bank line.

## Providing Useful Links

### Direct Links with Parameters

**Note**: Always verify Dolibarr's actual URL structure. Some pages may have changed:
- Invoice list: `compta/facture/list.php?socid=X` (not `compta/facture.php?socid=X`)
- VAT reports: `compta/tva/quadri_detail.php`
- Discounts/credits: `comm/remx.php?id=X` (X = company ID)

```php
// Current period
$year = date('Y');
$month = date('n');
$endday = cal_get_days_in_month(CAL_GREGORIAN, $month, $year);

// VAT report for current period
print '<a href="compta/tva/quadri_detail.php?invoice_type=customer&vat_rate_show=19.600&date_startyear='.$year.'&date_startmonth='.$month.'&date_startday=1&date_endyear='.$year.'&date_endmonth='.$month.'&date_endday='.$endday.'" target="_blank">View VAT Report</a>';

// View discounts/credits for a company
print '<a href="comm/remx.php?id='.$socid.'" class="butAction" target="_blank">View Discounts/Credits for Company</a>';
```

### Always Open in New Tab

```php
// All links should have target="_blank"
print '<a href="compta/facture/card.php?facid='.$invoiceid.'" class="butAction" target="_blank">View Invoice</a>';
```

## Teardown Logic

### Delete in Reverse Order

Always delete in reverse order of creation:
1. Discounts (most dependent) - Find discounts for ALL invoices and credit notes, not just a subset
2. Payments - Find payments for ALL invoices
3. Invoices and Credit Notes (sorted by numeric part of reference, so highest number deleted first)
4. Company (least dependent)

**Note**: For invoices and credit notes, sort by the numeric part of the ref field to ensure they are deleted in reverse order. Invoice references may have prefixes (e.g., "FACT-001", "CN-001") but share the same numbering sequence, so string sorting won't work correctly.

**Error Handling**: Use a loop to handle cascading deletions with foreign key constraints. Re-fetch and attempt deletion in multiple passes until a complete pass deletes nothing:

```php
// Multi-pass deletion to handle dependencies
$total_deleted = 0;
$pass_count = 0;
$max_passes = 10; // Safety limit

do {
    $pass_count++;
    $workdone = 0;
    
    // Re-fetch all items that need to be deleted
    $discounts = findRemainingDiscounts();
    foreach ($discounts as $discountid) {
        if (deleteDiscount($discountid)) {
            $workdone++;
            $total_deleted++;
        }
    }
    
    $invoices = findRemainingInvoices();
    foreach ($invoices as $inv) {
        if (deleteInvoice($inv)) {
            $workdone++;
            $total_deleted++;
        }
    }
    
    $companies = findRemainingCompanies();
    foreach ($companies as $socid) {
        if (deleteCompany($socid)) {
            $workdone++;
            $total_deleted++;
        }
    }
} while ($workdone > 0 && $pass_count < $max_passes);

echo "Deleted $total_deleted items in $pass_count passes.";
```

Always ensure all dependent records (discounts, payments, etc.) are found and deleted for ALL invoices and credit notes before attempting to delete the invoices themselves.

### Find Test Data by Pattern

```php
$timestamp_pattern = 'TESTXXXX_%';
$soc = new Societe($db);
$res = $soc->fetch_all('','','','','code_client LIKE \''.$db->escape($timestamp_pattern).'\'');
```

### Delete Discounts

```php
foreach ($discounts as $discountid) {
    $d = new DiscountAbsolute($db);
    $result = $d->fetch($discountid);
    if ($result > 0) {
        $result = $d->delete($user);
        if ($result > 0) {
            $deleted_count++;
        } else {
            $error_msg = $d->error ? dol_escape_htmltag($d->error) : '(no error message)';
            print '<p class="warning">Failed to delete discount ID: '.$discountid.' (Error: '.$error_msg.')</p>';
        }
    } else {
        print '<p class="warning">Discount ID: '.$discountid.' not found</p>';
    }
}
```

### Delete Invoices

```php
foreach ($invoices as $inv_id) {
    $inv = new Facture($db);
    $inv->fetch($inv_id);
    $result = $inv->delete($user);
    // handle result
}
```

### Delete Company

```php
$soc_to_delete = new Societe($db);
$soc_to_delete->fetch($socid);
$result = $soc_to_delete->delete($user);
```

## Complete Example

```php
<?php
require_once 'main.inc.php';
require_once 'compta/facture/class/facture.class.php';
require_once 'societe/class/societe.class.php';
require_once 'core/class/discount.class.php';

if (!class_exists('Discount')) {
    class_alias('DiscountAbsolute', 'Discount');
}

if (!$user->rights->facture->lire) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

llxHeader('', 'Issue #XXXX - Test Data');

if ($action == 'teardown') {
    // Find and delete test data
    // ... teardown code
} else {
    // Create test data
    // ... create code
}

// Show links
print '<a href="?action=teardown&token='.newToken().'" onclick="return confirm(\'Are you sure?\');">Delete Test Data</a>';

llxFooter();
```

## Output Formatting

Use Dolibarr's CSS classes for consistent styling:
- `fichecenter` - Centered content area
- `fichehalfleft` / `fichehalfright` - Half-width columns
- `ok` - Success messages (green)
- `warning` - Warning messages (orange)
- `error` - Error messages (red)
- `butAction` - Action buttons

## Flushing Output

For long-running operations, flush output:

```php
print '<p>Creating company...</p>';
print str_repeat(' ', 1024); // Trigger flush in buffered setups
ob_flush();
flush();
@ob_end_flush();
```

## Error Handling

```php
if ($res <= 0) {
    print '<p class="error">ERROR: '.dol_escape_htmltag($obj->error).'</p>';
    llxFooter();
    exit(1);
}
```

## Best Practices

1. **Unique Identifiers**: Always use timestamps or unique codes to avoid conflicts
2. **Reverse Deletion**: Delete dependent objects before their parents
3. **Confirmation**: Add JavaScript confirmation for destructive actions
4. **Error Reporting**: Enable error display during development
5. **Access Control**: Always check user permissions
6. **Dolibarr Integration**: Use llxHeader() and llxFooter() for proper page structure
7. **Link Targets**: Use `target="_blank"` for all external links
8. **Progress Feedback**: Show each step's progress to the user

## Testing Your Script

1. Visit the script URL in a browser while logged into Dolibarr
2. Verify the Create action works and shows all links
3. Verify the links open in new tabs
4. Verify the Teardown action finds and deletes all test data
5. Check that Dolibarr's authentication is respected

## Common Pitfalls

1. **Using master.inc.php instead of main.inc.php** - master.inc.php is for CLI, main.inc.php is for web
2. **Not checking permissions** - Always use accessforbidden() for restricted actions
3. **Hardcoded IDs** - Use timestamps or unique patterns to avoid conflicts
4. **Wrong deletion order** - Delete children before parents
5. **Missing requires** - Include all necessary class files
6. **Not escaping output** - Use dol_escape_htmltag() for user-facing error messages

## Debugging Database Errors

**InnoDB Monitor Output**: When Dolibarr outputs "SHOW ENGINE INNODB STATUS" (visible in logs as "We try to output some DB info"), the subsequent InnoDB monitor output may contain foreign key constraint errors or other database errors from BEFORE the test started. These errors are irrelevant to your test execution.

**How to verify**: Always check the timestamps in the error messages. Compare them with when your test started. Errors with timestamps BEFORE your test execution began are from previous operations and can be safely ignored.  Be aware about the timezone offsets.

Example:
```
########## We try to output some DB info

=====================================
2026-07-30 06:24:58 0x5828 Transaction:
LATEST FOREIGN KEY ERROR
Foreign key constraint fails for table `travis`.`llx25_facture`:
...
=====================================
2026-07-30 04:48:59 DEBUG   ???   sql=SELECT ...  // Your test actually started here
```

In this example, the foreign key error at 06:24:58 occurred before the test started at 04:48:59 (06:48:59 local time), so it's from a previous operation and is irrelevant.
