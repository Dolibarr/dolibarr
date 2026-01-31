# Anexum Module - Claude Code Documentation

## Module Overview

The **Anexum** module is a custom Dolibarr module developed for Anexum GmbH that provides company-specific customizations and business logic enhancements. It focuses on user experience improvements, permission filtering, and conditional extrafield display.

**Author**: Guenter Lukas Consulting (https://gl.co.at)
**Version**: 1.4.0
**License**: GPL-3.0+
**Module ID**: 500011

## Purpose & Features

The Anexum module provides the following key features:

1. **External User Access Control**
   - Hides draft proposals, orders, and invoices from external users (category ID 8)
   - Filters contact lists to show only contacts from companies where the user is assigned as sales representative
   - Redirects external users away from draft records

2. **Dynamic Ticket Extrafield Management**
   - Conditionally shows/hides ticket extrafields based on ticket type selection
   - Uses JavaScript to dynamically toggle extrafield visibility
   - Supports `iftype_*` naming convention for conditional extrafields

3. **External User Filter Replacement**
   - Replaces `$EXT_USER_FILTER$` placeholder in extrafield options with dynamic SQL conditions
   - For internal users: Shows all records (`1=1`)
   - For external users: Filters by their company (`fk_soc=$user->socid`)

4. **Stuck Cron Job Recovery**
   - CLI script to detect and reset cron jobs stuck in "processing" state
   - Runs as a scheduled job every 5 minutes
   - Fixes infinite loop issues in EmailCollector and other cron jobs

## Module Structure

```
htdocs/custom/anexum/
├── core/
│   └── modules/
│       └── modAnexum.class.php          # Module descriptor (extends DolibarrModules)
├── class/
│   └── actions_anexum.class.php         # Hook implementations (ActionsAnexum class)
├── lib/
│   └── anexum.lib.php                   # Helper functions (anexumAdminPrepareHead)
├── admin/
│   ├── setup.php                        # Module configuration page
│   └── about.php                        # About page
├── langs/
│   └── en_US/
│       └── anexum.lang                  # English translations
├── sql/
│   └── dolibarr_allversions.sql         # SQL upgrade script (empty)
├── img/                                 # Module images
├── test/                                # PHPUnit tests
├── bin/
│   └── reset_stuck_cron.php             # CLI script to reset stuck cron jobs
├── README.md                            # Module documentation
├── ChangeLog.md                         # Version history
└── anexumindex.php                      # Module home page
```

## Key Components

### 1. Module Descriptor (`core/modules/modAnexum.class.php`)

**Location**: `htdocs/custom/anexum/core/modules/modAnexum.class.php`

**Key Properties**:
- **Module ID**: `500011` (registered in Dolibarr module registry)
- **Family**: `other`
- **Version**: `1.4.0`
- **PHP Requirements**: PHP 5.6+
- **Dolibarr Requirements**: 11.x+

**Hook Contexts** (lines 117-132):
```php
'hooks' => array(
    'data' => array(
        'publicnewticketcard',  // Public ticket creation form
        'ticketcard',           // Ticket detail/edit page
        'agenda',               // Calendar view
        'agendalist',           // Calendar list
        'propallist',           // Proposal list
        'propalcard',           // Proposal detail
        'orderlist',            // Order list
        'ordercard',            // Order detail
        'invoicelist',          // Invoice list
        'invoicecard',          // Invoice detail
        'contactlist'           // Contact list
    ),
    'entity' => '0',
)
```

**Permissions** (lines 274-293):
- Read objects of Anexum (`$user->rights->anexum->myobject->read`)
- Create/Update objects (`$user->rights->anexum->myobject->write`)
- Delete objects (`$user->rights->anexum->myobject->delete`)

### 2. Hook Actions Class (`class/actions_anexum.class.php`)

**Location**: `htdocs/custom/anexum/class/actions_anexum.class.php`

**Key Methods**:

#### `doActions($parameters, &$object, &$action, $hookmanager)` (lines 100-228)

**Purpose**: Main hook handler for form actions and dynamic extrafield management.

**Key Features**:
1. **External User Filtering**: Calls `replaceExtUserFilter()` to replace filter placeholders
2. **Draft Hiding**: Calls `hideDraftsForExternalUsersCard()` to redirect from draft records
3. **Dynamic Ticket Extrafields**: Shows/hides ticket extrafields based on ticket type

**Ticket Extrafield Logic** (lines 110-224):
- Executes on contexts: `publicnewticketcard`, `ticketcard` with actions `create`, `edit`
- Fetches all ticket extrafields starting with `iftype_`
- Generates JavaScript to hide/show extrafields based on ticket type selection
- Uses naming convention: `iftype_<typename>_<fieldname>` (e.g., `iftype_com_priority`)

**JavaScript Generation**:
```javascript
// Hide all conditional extrafields on page load
$('.ticket_extras_iftype_*').hide();

// On ticket type change, show relevant extrafields
$('#selecttype_code').on('change', function() {
    if($('#selecttype_code').val() == 'COM') {
        $('.ticket_extras_iftype_com_*').show();
    }
});
```

#### `printFieldListWhere($parameters, &$object, &$action, $hookmanager)` (lines 422-436)

**Purpose**: Adds SQL WHERE conditions to filter list views.

**Returns**: `1` to append `$this->resprints` to SQL WHERE clause.

**Filters**:
- Calls `hideDraftsForExternalUsersList()` for proposals, orders, invoices
- Calls `filterContactsForExternalUsers()` for contact list

#### `hideDraftsForExternalUsersList($parameters, &$object, &$action)` (lines 544-606)

**Purpose**: Filters out draft records for external users (category ID 8).

**SQL Conditions**:
- **Proposals/Orders**: `AND p.fk_statut != 0` (hides status 0 = draft)
- **Invoices**: `AND f.fk_statut != 0` (different table alias)

**External User Detection** (lines 550-566):
```php
$targetCategoryId = 8; // "Externe" category
$category = new Categorie($db);
$userCategories = $category->containing($user->id, 'user');

foreach ($userCategories as $cat) {
    if ($cat->id == $targetCategoryId) {
        return " AND p.fk_statut != 0"; // Hide drafts
    }
}
```

#### `hideDraftsForExternalUsersCard($parameters, &$object, &$action)` (lines 616-652)

**Purpose**: Redirects external users away from draft proposal/order/invoice cards.

**Logic** (lines 640-648):
```php
if ($isInTargetCategory) {
    if ($object->status == 0) { // Draft status
        header('Location: ' . DOL_URL_ROOT . '/index.php?');
        exit;
    }
}
```

**Contexts**: `propalcard`, `ordercard`, `invoicecard`

#### `filterContactsForExternalUsers($parameters, &$object, &$action)` (lines 444-470)

**Purpose**: Filters contact list to show only contacts from companies where the user is sales representative.

**SQL Condition** (lines 462-465):
```php
return " AND EXISTS (
    SELECT 1 FROM llx_societe_commerciaux sc
    WHERE sc.fk_soc = s.rowid AND sc.fk_user = " . ((int) $user->id) . "
)";
```

**Note**: Only applies to external users in category ID 8.

#### `replaceExtUserFilter(&$parameters, $action)` (lines 655-694)

**Purpose**: Replaces `$EXT_USER_FILTER$` placeholder in extrafield options with dynamic SQL.

**Use Case**: Allows extrafields to filter records based on user type.

**Replacement Logic** (lines 681-687):
```php
if (empty($user->socid)) {
    // Internal user - show all records
    $repl = "1=1";
} else {
    // External user - filter by their company
    $repl = "fk_soc=$user->socid";
}
$newkey = str_replace('$EXT_USER_FILTER$', $repl, $oldkey);
```

**Example**:
- Extrafield option: `SELECT rowid, ref FROM llx_contrat WHERE $EXT_USER_FILTER$`
- Internal user: `SELECT rowid, ref FROM llx_contrat WHERE 1=1`
- External user: `SELECT rowid, ref FROM llx_contrat WHERE fk_soc=123`

**Contexts**: `publicnewticketcard`, `ticketcard` (create/edit/edit_extras/update_extras actions)

### 3. CLI Scripts (`bin/`)

#### `reset_stuck_cron.php`

**Location**: `htdocs/custom/anexum/bin/reset_stuck_cron.php`

**Purpose**: Detects and resets Dolibarr cron jobs that are stuck in "processing" state. This fixes issues where EmailCollector or other cron jobs get stuck in infinite loops, blocking subsequent cron runs.

**Usage**:
```bash
# Normal run (silent unless something is reset)
php htdocs/custom/anexum/bin/reset_stuck_cron.php

# Verbose output
php htdocs/custom/anexum/bin/reset_stuck_cron.php --verbose

# Dry-run (preview only, no changes)
php htdocs/custom/anexum/bin/reset_stuck_cron.php --dry-run

# Custom threshold (60 minutes instead of default 30)
php htdocs/custom/anexum/bin/reset_stuck_cron.php --threshold=60
```

**Options**:
- `-v, --verbose` - Show detailed output
- `-n, --dry-run` - Preview what would be reset without making changes
- `--threshold=MIN` - Minutes before a job is considered stuck (default: 30)
- `-h, --help` - Show help message

**Logic**:
1. Finds jobs where `processing=1` AND `datelastrun < NOW() - threshold`
2. If job has a PID, checks if process is still alive (Linux `/proc` filesystem)
3. Resets stuck jobs by setting `processing=0` and `pid=NULL`
4. Logs all resets to Dolibarr syslog

**Scheduled Job**: Registered as a Dolibarr scheduled job running every 5 minutes.

### 4. Helper Library (`lib/anexum.lib.php`)

**Location**: `htdocs/custom/anexum/lib/anexum.lib.php`

#### `anexumAdminPrepareHead()` (lines 29-68)

**Purpose**: Generates admin page tab navigation.

**Returns**: Array of tab definitions for admin pages.

**Tabs**:
1. **Settings** (`admin/setup.php`)
2. **About** (`admin/about.php`)
3. Dynamic tabs from other modules (via `complete_head_from_modules`)

## Configuration

### Admin Settings

**Location**: `htdocs/custom/anexum/admin/setup.php`

The module provides a standard configuration page accessible at:
- **Path**: Home → Setup → Modules → Anexum → Settings

**Available Settings**:
- `ANEXUM_MYPARAM1` - Custom parameter 1
- `ANEXUM_MYPARAM2` - Custom parameter 2

### Language Translations

**Location**: `htdocs/custom/anexum/langs/en_US/anexum.lang`

**Key Translations**:
- `ModuleAnexumName` - Module name displayed in UI
- `ModuleAnexumDesc` - Module description
- `AnexumSetup` - Setup page title
- Admin page labels and tooltips

## Version History (ChangeLog.md)

### 1.3.0
- FIX: Filter for invoice list
- ADD: Filter for external user contact list

### 1.2.2
- FIX: Extrafields were hidden in invoice/propal/order cards when editing

### 1.2.1
- ADD: Invoice List and Card Context

### 1.2
- Hide Draft Propals and Orders for users with category extern

### 1.1
- Migrate to PHP 8

### 1.0
- Initial version

## External User Category

The module uses **Category ID 8** (German: "Externe") to identify external users throughout all filtering logic.

**Key Behaviors for External Users**:
1. Cannot view draft proposals, orders, or invoices
2. Redirected away from draft record detail pages
3. Contact list filtered to assigned companies only
4. Extrafield options filtered by company (`fk_soc`)

## Integration Points

### With Dolibarr Core

1. **User Categories** (`llx_categorie`, `llx_categorie_user`)
   - Used to identify external users (category 8)

2. **Ticket Extrafields** (`llx_ticket_extrafields`)
   - Conditional display based on ticket type
   - Dynamic filter replacement for external users

3. **Sales Representatives** (`llx_societe_commerciaux`)
   - Used to filter contacts for external users

4. **Object Status Fields**
   - Proposals: `p.fk_statut`
   - Orders: `p.fk_statut`
   - Invoices: `f.fk_statut`

### Hook Execution Flow

```
Page Load (e.g., ticketcard.php)
    ↓
initHooks('ticketcard')
    ↓
executeHooks('doActions', ...)
    ↓
ActionsAnexum::doActions()
    ↓
├── replaceExtUserFilter()      # Replace $EXT_USER_FILTER$ placeholder
├── hideDraftsForExternalUsersCard()  # Redirect if draft
└── Dynamic extrafield JS generation  # Show/hide by ticket type
```

## Development Guidelines

### Adding New Conditional Extrafields

To add a new ticket extrafield that shows only for a specific ticket type:

1. **Create Extrafield** with naming convention:
   ```
   iftype_<tickettype>_<fieldname>
   ```
   Example: `iftype_com_escalation_level` (shows only for ticket type "COM")

2. **No Code Changes Required**
   - The `doActions()` method automatically detects `iftype_*` fields
   - JavaScript is generated dynamically based on field names

### Adding New External User Filters

To add filtering for a new list view:

1. **Add Hook Context** to `modAnexum.class.php`:
   ```php
   'hooks' => array(
       'data' => array(
           'mynewlist',  // New list context
       ),
   )
   ```

2. **Extend `printFieldListWhere()` in `actions_anexum.class.php`:
   ```php
   if (in_array($parameters['currentcontext'], array('mynewlist'))) {
       // Check if user is external (category 8)
       if ($isInTargetCategory) {
           return " AND t.fk_statut != 0"; // Filter condition
       }
   }
   ```

### External User Filter in Extrafields

To use dynamic filtering in select extrafields:

1. **Create Select Extrafield** with SQL option:
   ```sql
   SELECT rowid, ref FROM llx_mytable WHERE $EXT_USER_FILTER$
   ```

2. **Add Hook Context** (if not already present):
   ```php
   'ticketcard', 'propalcard', etc.
   ```

3. **Filter Applies Automatically**:
   - Internal users: `WHERE 1=1` (all records)
   - External users: `WHERE fk_soc=<user_company_id>`

## Testing

### Manual Testing

1. **External User Draft Filtering**:
   - Create user in category "Externe" (ID 8)
   - Create draft proposal/order/invoice
   - Verify user cannot see draft in list
   - Verify redirect when accessing draft URL directly

2. **Contact List Filtering**:
   - Login as external user (category 8)
   - Assign user as sales representative to specific companies
   - Navigate to Contacts list
   - Verify only contacts from assigned companies are visible

3. **Dynamic Ticket Extrafields**:
   - Create extrafields: `iftype_com_field1`, `iftype_req_field2`
   - Create/edit ticket
   - Change ticket type and verify fields show/hide correctly

### Automated Testing

**Test Location**: `htdocs/custom/anexum/test/phpunit/`

Run tests:
```bash
cd /var/www/html
phpunit htdocs/custom/anexum/test/phpunit/
```

## Common Issues & Troubleshooting

### Issue: Extrafields Not Hiding/Showing

**Symptom**: Conditional ticket extrafields don't toggle when ticket type changes.

**Solution**:
1. Check extrafield naming: Must start with `iftype_`
2. Verify ticket type code matches field name (e.g., `iftype_com_*` for type "COM")
3. Check browser console for JavaScript errors
4. Ensure ticket type is active (`llx_c_ticket_type.active = 1`)

### Issue: External Users See Draft Records

**Symptom**: Users in category 8 can still see draft proposals/orders/invoices.

**Solution**:
1. Verify user is in category ID 8: `SELECT * FROM llx_categorie_user WHERE fk_user = <user_id>`
2. Check hook is registered in `modAnexum.class.php` for the list context
3. Verify module is enabled: Home → Setup → Modules → Anexum

### Issue: Contact List Shows All Contacts for External User

**Symptom**: External user sees contacts from all companies, not just assigned ones.

**Solution**:
1. Verify user is assigned as sales representative:
   ```sql
   SELECT * FROM llx_societe_commerciaux WHERE fk_user = <user_id>
   ```
2. Check user is in category 8
3. Verify `contactlist` hook context is registered

### Issue: $EXT_USER_FILTER$ Not Replaced

**Symptom**: Extrafield options show literal `$EXT_USER_FILTER$` text instead of SQL.

**Solution**:
1. Verify hook context includes the page where extrafield is used
2. Check extrafield option has exactly one option (placeholder replacement only works for single options)
3. Ensure action is one of: `create`, `edit`, `edit_extras`, `update_extras`

## Code Quality

### Coding Standards

The module follows Dolibarr coding standards:
- **PHP Version**: 5.6+ (8.x compatible)
- **Indentation**: Tabs
- **Line Endings**: Unix (LF)
- **Comments**: PHPDoc blocks for all methods

### Static Analysis

Run PHPStan:
```bash
phpstan analyse htdocs/custom/anexum/ --level=5
```

### Code Style Check

```bash
dolibarr-dev cs-check htdocs/custom/anexum/
```

### Code Style Fix

```bash
dolibarr-dev cs-fix htdocs/custom/anexum/
```

## Dependencies

### Required Modules

- None (standalone module)

### Optional Integrations

- Works alongside other Anexum custom modules (doliprom, audit, etc.)
- No conflicts reported

### External Libraries

- None (uses only Dolibarr core classes)

## Security Considerations

### SQL Injection Prevention

All SQL conditions use proper escaping:
```php
// Good: Integer casting
$user->id → ((int) $user->id)

// Good: Database escape
$db->escape($value)
```

### Access Control

- Uses Dolibarr's category system for user classification
- Relies on `$user->socid` for external user identification
- Filters applied at SQL level (not just UI)

### XSS Prevention

- JavaScript generation uses server-side PHP escaping
- No user input directly injected into JavaScript

## Future Enhancements

Possible improvements for future versions:

1. **Configurable External Category ID**
   - Currently hardcoded as category 8
   - Could be made configurable via admin settings

2. **Custom Draft Status Configuration**
   - Allow different status codes for different object types
   - Make draft status configurable per entity

3. **Enhanced Logging**
   - Add debug logging for filter application
   - Track when external users are redirected

4. **Performance Optimization**
   - Cache user category lookups
   - Reduce database queries for repeated category checks

## Support & Contact

**Developer**: Guenter Lukas Consulting
**Website**: https://gl.co.at
**Support**: https://support.gl.co.at

For issues specific to Anexum GmbH deployment, contact the internal development team.

## License

This module is licensed under GPL-3.0 or later.
See the COPYING file for full license text.
