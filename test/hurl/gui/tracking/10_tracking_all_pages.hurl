# Comprehensive test for tracking functionality on all 4 pages
# Tests that tracking information displays correctly and can be edited

# Step 1: Access the test page to get CSRF token
GET http://{{hostnport}}/test_shipping_tracking.php
HTTP 200
[Asserts]
body not contains "Enter login details"
body contains "Shipping Tracking Test"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
[Captures]
token: xpath "normalize-space(//meta[@name='anti-csrf-newtoken']/@content)"

# Step 2: Create test data (creates supplier, order, invoice, expedition, reception with tracking)
GET http://{{hostnport}}/test_shipping_tracking.php?action=create&token={{token}}
HTTP 200
[Asserts]
body contains "Supplier created"
body contains "Supplier Order created"
body contains "Supplier Invoice created"
body contains "Expedition created"
body contains "Reception created"
body contains "View Supplier Order"
body contains "View Supplier Invoice"
body contains "View Expedition"
body contains "View Reception"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
[Captures]
test_orderid: xpath "string(//a[contains(@href, 'fourn/commande/card.php?id=')]/@href)"
test_invoiceid: xpath "string(//a[contains(@href, 'fourn/facture/card.php?id=')]/@href)"
test_expeditionid: xpath "string(//a[contains(@href, 'expedition/card.php?id=')]/@href)"
test_receptionid: xpath "string(//a[contains(@href, 'reception/card.php?id=')]/@href)"

# Step 3: Verify tracking display on supplier order page - initial setup
GET http://{{hostnport}}/{{test_orderid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
body contains "Tracking number"

# Step 4: Edit tracking information on supplier order
GET http://{{hostnport}}/{{test_orderid}}?action=edit_tracking&token={{token}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"

# Step 5: Submit new tracking information for supplier order
POST http://{{hostnport}}/{{test_orderid}}&action=set_tracking&token={{token}}&tracking_awb=NEWTRACK123&tracking_link=https%3A%2F%2Fnewtrack.example.com&carrier_code=FX
HTTP 302
[Asserts]
header "Location" matches "^/fourn/commande/card.php[?]id=[0-9]+$"

# Step 6: Verify updated tracking display on supplier order page
GET http://{{hostnport}}/{{test_orderid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning: [^I]"
body matches "Warning: Invoice.*has different tracking"
body contains "NEWTRACK123"

# Step 7: Verify tracking display on supplier invoice page - initial setup
GET http://{{hostnport}}/{{test_invoiceid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
body contains "Tracking number"

# Step 8: Edit tracking information on supplier invoice
GET http://{{hostnport}}/{{test_invoiceid}}?action=edit_tracking&token={{token}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"

# Step 9: Submit new tracking information for supplier invoice
POST http://{{hostnport}}/{{test_invoiceid}}&action=set_tracking&token={{token}}&tracking_awb=NEWINVTRACK456&tracking_link=https%3A%2F%2Finvtrack.example.com&carrier_code=FX
HTTP 302
[Asserts]
header "Location" matches "^/fourn/facture/card.php[?]id=[0-9]+$"

# Step 10: Verify updated tracking display on supplier invoice page
GET http://{{hostnport}}/{{test_invoiceid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
body contains "NEWINVTRACK456"

# Step 11: Verify tracking display on expedition page
GET http://{{hostnport}}/{{test_expeditionid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"

# Step 12: Verify tracking display on reception page
GET http://{{hostnport}}/{{test_receptionid}}
HTTP 200
[Asserts]
body not contains "Enter login details"
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"

# Step 13: Teardown - delete all test data
GET http://{{hostnport}}/test_shipping_tracking.php?action=teardown&token={{token}}
HTTP 200
[Asserts]
#body contains "Test data deleted successfully ("
body not contains "xdebug-error"
body not contains "Error:"
body not contains "Warning:"
