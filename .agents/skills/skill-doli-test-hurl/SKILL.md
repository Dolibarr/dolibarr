---
name: skill-doli-test-hurl
description: Create and maintain Hurl tests for Dolibarr ERP/CRM. Use when asked to create, write, or fix hurl tests for Dolibarr, or when working with test/hurl directory. Covers Dolibarr-specific conventions for hurl testing including authentication, test file naming, and test structure.
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
 - bash
---

# Dolibarr Hurl Test Skill

## Description
Create and maintain Hurl tests for Dolibarr ERP/CRM. Use when asked to create, write, or fix hurl tests for Dolibarr, or when working with test/hurl directory. This skill covers the Dolibarr-specific conventions for hurl testing including authentication, test file naming, and test structure.

**IMPORTANT**: Add `body not contains "xdebug-error"` assertions to Hurl tests (and also for each {Error:,Warning:,notice,warning}).

**NOTE**: Hurl uses Rust regex syntax, which does not support negative lookaheads (`(?!...)`). Use character class exclusions like `[^X]` instead.

## When to Use
- User asks to create hurl tests for Dolibarr
- User asks to fix failing hurl tests
- Working with test/hurl directory
- Need to test Dolibarr GUI or API endpoints
- User mentions "hurl test" or "hurl tests"

## Dolibarr Hurl Test Conventions

### File Naming Pattern
Hurl test files must follow a specific naming pattern based on the number prefix:

| Prefix | Description | Authentication Required |
|--------|-------------|------------------------|
| 00 | No authentication required | No |
| 10 | Authentication required (GUI tests) | Yes |
| 20 | Reserved for future use | - |
| 30 | POST data for later tests | Yes |
| 40 | GET data that was POST'ed in 30_ files | Yes |
| 50 | PUT data that was POST'ed in 30_ files | Yes |
| 60 | GET data that was PUT'ed in 50_ files | Yes |
| 70 | DELETE data | Yes |
| 80 | GET data that was just DELETE'd | Yes |
| 90 | Reserved for future use | - |

**Important**: For Dolibarr GUI tests that require authentication, use the `10` prefix (not 30, 40, etc.). The run.sh script only executes GUI tests matching `*/10*.hurl`.

### File Structure
```
test/hurl/
├── api/                    # API endpoint tests
│   ├── 00_*.hurl           # No auth required
│   └── 10_*.hurl           # Auth required
├── gui/                    # GUI/browser tests
│   ├── 00_*.hurl           # No auth required
│   ├── 10_*.hurl           # Auth required (most GUI tests)
│   └── save_login_cookie.hurl  # Special file for login
└── public/                # Public endpoint tests
    └── 00_*.hurl
```

### Directory Structure
Place tests in directories matching the Dolibarr module/path structure:
- `test/hurl/gui/fourn/commande/` - Supplier order tests
- `test/hurl/gui/societe/` - Third party/company tests
- `test/hurl/api/commande/` - Customer order API tests
- `test/hurl/api/fourn/` - Supplier API tests

### Authentication
Dolibarr requires authentication for most GUI operations. The hurl framework handles this through:

1. **save_login_cookie.hurl** - Special file that logs in and saves cookies
2. **Cookie jar** - Passed via `--cookiefile` parameter
3. **CSRF token** - Captured from login page and passed with requests

#### Capturing CSRF Token
```hurl
# Get the login page to capture CSRF token
GET http://{{hostnport}}/
HTTP 200
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

# Use the token in subsequent requests
POST http://{{hostnport}}/some/page.php
[FormParams]
action: some_action
token: {{token}}
```

### Creating Test Data

#### Supplier Creation
Suppliers (fournisseurs) are created via `societe/card.php` with `type=f`:
```hurl
POST http://{{hostnport}}/societe/card.php?leftmenu=suppliers&action=create&type=f
HTTP 200
[FormParams]
name: Test Supplier {{timestamp}}
code_fournisseur: TEST_{{timestamp}}
fournisseur: 1
client: 0
email: test-{{timestamp}}@example.com
address: 
zip: 
town: 
country_id: 1
token: {{token}}

# Capture the supplier ID
GET http://{{hostnport}}/societe/card.php?socid={{socid}}&leftmenu=suppliers
HTTP 200
[Captures]
socid: queryParam "socid"
```

#### Supplier Order Creation
```hurl
POST http://{{hostnport}}/fourn/commande/card.php?action=create
HTTP 200
[FormParams]
socid: {{socid}}
date: {{now}}
date_livraison: {{now_plus_7_days}}
ref_supplier: ORDER-{{timestamp}}
statut: 0
token: {{token}}

# Capture the order ID
GET http://{{hostnport}}/fourn/commande/card.php?id={{orderid}}
HTTP 200
[Captures]
orderid: queryParam "id"
```

#### Customer Order Creation
```hurl
POST http://{{hostnport}}/commande/card.php?action=create
HTTP 200
[FormParams]
socid: {{socid}}
date: {{now}}
date_livraison: {{now_plus_7_days}}
ref_customer: ORDER-{{timestamp}}
statut: 0
token: {{token}}
```

### Assertions
Use Dolibarr-specific assertions:

```hurl
# Check for tracking number display
GET http://{{hostnport}}/fourn/commande/card.php?id={{orderid}}
HTTP 200
[Asserts]
body contains "FX123456789012"
body contains "FedEx"
body contains "TrackingNumber"
body contains "https://www.fedex.com/tracking/FX123456789012"
```

#### Handling Expected Warnings
When testing for expected warning messages while catching unexpected ones:
```hurl
# Expect a specific warning but catch others
GET http://{{hostnport}}/some/page.php
HTTP 200
[Asserts]
body contains "Warning: Invoice.*has different tracking"
body not contains "Warning: [^I]"
```
This allows the expected "Invoice..." warning while catching any other warnings that don't start with "I".

#### Regex Patterns
Hurl uses Rust regex syntax. Important differences from PCRE:
- No negative lookahead support: `(?!pattern)` is NOT supported
- Use character class exclusions: `[^X]` to match any character except X
- To match a literal `?` in URLs, use `[?]` not `\?`
- Start anchors: Use `^` for start of string

```hurl
# Match redirect Location header with query parameter
header "Location" matches "^/fourn/facture/card.php[?]id=[0-9]+$"

# Match any warning except those starting with "I"
body not contains "Warning: [^I]"
```

### Running Tests

#### Using run_with_server.sh
The recommended way to run hurl tests locally:
```bash
# Run specific test path
test/hurl/run_with_server.sh gui/fourn/commande

# Run with custom port
test/hurl/run_with_server.sh --port=9000 gui/fourn/commande
```

#### Using run.sh Directly
```bash
# Set environment variables
export DOLIHOST="127.0.50.1"
export DOLIPORT="9999"
export DOLIUSERNAME="admin"
export DOLIPASSWORD="admin"
export COOKIEJAR="/tmp/hurl_cookie.jar"

# Run tests
test/hurl/run.sh --cookiefile="$COOKIEJAR" --port="$DOLIPORT" --host="$DOLIHOST" --user="$DOLIUSERNAME" --pass="$DOLIPASSWORD" gui/fourn/commande
```

#### Running Specific Tests
```bash
# Run all tests containing "tracking"
test/hurl/run_with_server.sh tracking

# Run tests in a specific directory
test/hurl/run_with_server.sh gui/fourn

# Run specific test file
test/hurl/run_with_server.sh 10_supplier_order_tracking_POST
```

### Test File Template

#### GUI Test with Authentication (10 prefix)
```hurl
# Test description
POST http://{{hostnport}}/module/page.php?action=create
HTTP 200
[FormParams]
field1: value1
field2: value2
token: {{token}}

# Capture created ID
GET http://{{hostnport}}/module/page.php?id={{created_id}}
HTTP 200
[Captures]
created_id: queryParam "id"

# Verify the creation
GET http://{{hostnport}}/module/page.php?id={{created_id}}
HTTP 200
[Asserts]
body contains "value1"
body contains "value2"
body not contains "warning"
body not contains "error"
body not contains "notice"
body not contains "Warning:"
body not contains "Error:"
body not contains "xdebug-error"
```

#### API Test with Authentication (10 prefix)
```hurl
# API tests use the DOLAPIKEY header
GET http://{{hostnport}}/api/index.php/orders
HTTP 200
DOLAPIKEY: DOLAPIKEY: your_api_key_here
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
body not contains "warning"
body not contains "error"
body not contains "notice"
body not contains "Warning:"
body not contains "Error:"
```

### Built-in Variables
Hurl provides useful built-in variables:

| Variable | Description | Example |
|----------|-------------|---------|
| {{timestamp}} | Current timestamp | 1712345678 |
| {{now}} | Current date/time | 2024-04-05T12:34:56 |
| {{now_plus_7_days}} | Date 7 days from now | 2024-04-12T12:34:56 |
| {{hostnport}} | Host and port | 127.0.50.1:9999 |
| {{token}} | CSRF token (captured) | abc123... |
| {{username}} | Username | admin |
| {{password}} | Password | admin |

### Best Practices

1. **Always include token in POST requests**
    ```hurl
    [FormParams]
    action: create
    token: {{token}}
    ```

2. **Use proper URL patterns**
    - Supplier creation: `societe/card.php?leftmenu=suppliers&action=create&type=f`
    - Customer creation: `societe/card.php?leftmenu=customers&action=create&type=c`
    - Supplier order: `fourn/commande/card.php`
    - Customer order: `commande/card.php`

3. **Capture IDs for cleanup**
   Always capture created IDs so they can be used in subsequent tests and cleaned up:
   ```hurl
   [Captures]
   socid: queryParam "socid"
   orderid: queryParam "id"
   ```

4. **Clean up test data**
   Create DELETE tests (70_ prefix) to remove test data:
   ```hurl
   POST http://{{hostnport}}/fourn/commande/card.php?action=delete
   HTTP 200
   [FormParams]
   id: {{orderid}}
   token: {{token}}
   ```

5. **Use proper HTTP status codes**
   - 200 for successful operations
   - 404 for deleted/not found resources
   - 400 for bad requests

6. **Test both positive and negative cases**
    - Test valid inputs
    - Test invalid inputs
    - Test edge cases

7. **Keep tests small and focused**
    Each test file should test one specific functionality.

8. **Handle expected messages properly**
    - Use `body contains "Expected message"` for messages you expect to see
    - Use `body not contains "Error:"` and `body not contains "Warning:"` for unexpected errors
    - For expected warnings, check for the specific warning text and use exclusion patterns for unexpected ones

### Common Issues and Fixes

#### Issue: Tests not being found
**Cause**: Wrong file name prefix
**Fix**: Use `10_` prefix for GUI tests that require authentication

#### Issue: Authentication failing
**Cause**: Missing token or cookie
**Fix**: Ensure `save_login_cookie.hurl` runs first and token is captured

#### Issue: CSRF protection blocking requests
**Cause**: Missing token parameter
**Fix**: Add `token: {{token}}` to all POST requests with actions

#### Issue: Redirects not being followed
**Cause**: Hurl doesn't follow redirects by default
**Fix**: Use separate GET requests to follow redirects, capture IDs from URLs

#### Issue: Regex patterns not working
**Cause**: Using PCRE syntax (like `(?!...)` negative lookahead) which Rust regex doesn't support
**Fix**: Use Rust-compatible regex syntax. For excluding patterns, use character class exclusions like `[^X]` instead of negative lookaheads

#### Issue: URL query parameter matching
**Cause**: Using `\?` to match literal `?` in URLs
**Fix**: Use `[?]` to match literal question marks in regex patterns

#### Issue: Expected warnings being flagged as failures
**Cause**: Using `body not contains "Warning:"` when some warnings are expected
**Fix**: Use `body contains "Warning: ExpectedMessage"` for expected warnings and `body not contains "Warning: [^E]"` to catch unexpected warnings starting with other characters

#### Issue: Tests running against wrong environment
**Cause**: Hardcoded URLs
**Fix**: Use `{{hostnport}}` variable and proper environment setup

### Example: Complete Test Suite for Supplier Order Tracking

**File: test/hurl/gui/fourn/commande/10_supplier_order_tracking_POST.hurl**
```hurl
# Create a supplier for tracking test
POST http://{{hostnport}}/societe/card.php?leftmenu=suppliers&action=create&type=f
HTTP 200
[FormParams]
name: Test Supplier for Tracking {{timestamp}}
code_fournisseur: TRACKTEST_{{timestamp}}
fournisseur: 1
client: 0
email: test-supplier-{{timestamp}}@example.com
country_id: 1
token: {{token}}

# Capture the supplier ID
GET http://{{hostnport}}/societe/card.php?socid={{socid}}&leftmenu=suppliers
HTTP 200
[Captures]
socid: queryParam "socid"

# Create a supplier order
POST http://{{hostnport}}/fourn/commande/card.php?action=create
HTTP 200
[FormParams]
socid: {{socid}}
date: {{now}}
date_livraison: {{now_plus_7_days}}
ref_supplier: ORDER-{{timestamp}}
statut: 0
token: {{token}}

# Capture the order ID
GET http://{{hostnport}}/fourn/commande/card.php?id={{orderid}}
HTTP 200
[Captures]
orderid: queryParam "id"

# Set tracking information
POST http://{{hostnport}}/fourn/commande/card.php?action=set_tracking
HTTP 200
[FormParams]
id: {{orderid}}
tracking_awb: FX123456789012
carrier_code: FX
token: {{token}}
```

**File: test/hurl/gui/fourn/commande/10_supplier_order_tracking_GET.hurl**
```hurl
# Verify tracking information is displayed
GET http://{{hostnport}}/fourn/commande/card.php?id={{orderid}}
HTTP 200
[Asserts]
body contains "FX123456789012"
body contains "FedEx"
body contains "TrackingNumber"
body contains "https://www.fedex.com/tracking/FX123456789012"
```

**File: test/hurl/gui/fourn/commande/10_supplier_order_tracking_DELETE.hurl**
```hurl
# Delete the supplier order
POST http://{{hostnport}}/fourn/commande/card.php?action=delete
HTTP 200
[FormParams]
id: {{orderid}}
token: {{token}}

# Verify order is deleted
GET http://{{hostnport}}/fourn/commande/card.php?id={{orderid}}
HTTP 404

# Delete the supplier
POST http://{{hostnport}}/societe/card.php?leftmenu=suppliers&action=delete
HTTP 200
[FormParams]
socid: {{socid}}
token: {{token}}

# Verify supplier is deleted
GET http://{{hostnport}}/societe/card.php?socid={{socid}}&leftmenu=suppliers
HTTP 404
```

### Validation
Before committing hurl tests:
1. Run the tests locally with `test/hurl/run_with_server.sh <test_path>`
2. Verify all tests pass
3. Check that cleanup works (DELETE tests)
4. Ensure no test data is left behind

### References
- [Hurl Documentation](https://hurl.dev/docs)
- [Dolibarr Hurl Test README](test/hurl/README.md)
- [Existing Dolibarr Hurl Tests](test/hurl/)
