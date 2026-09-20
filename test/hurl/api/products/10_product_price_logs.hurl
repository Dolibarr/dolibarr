# ==============================================================================
# Dynamic Product Purchase Price Logs Tests
# ==============================================================================

# SETUP: Capture a valid Product ID from the first available product
GET http://{{hostnport}}/api/index.php/products?limit=1
HTTP 200
[Captures]
target_id: jsonpath "$[0].id"

# Validation: Test with ID 0 (Should fail)
GET http://{{hostnport}}/api/index.php/products/0/purchase_prices/logs
HTTP 400
{"error":{"code":400,"message":"Bad Request: bad value for parameter id, ref, ref_ext or barcode"}}

# Validation: Test with a non-existent ID (Should fail)
GET http://{{hostnport}}/api/index.php/products/999999/purchase_prices/logs
HTTP 404
{"error":{"code":404,"message":"Not Found: Product not found"}}

# Success: Fetch logs using the CAPTURED ID
GET http://{{hostnport}}/api/index.php/products/{{ target_id }}/purchase_prices/logs
HTTP 200

# Query Param: Test with pagination_data=false
GET http://{{hostnport}}/api/index.php/products/{{ target_id }}/purchase_prices/logs?pagination_data=false
HTTP 200

# Query Param: Test with pagination_data=true
GET http://{{hostnport}}/api/index.php/products/{{ target_id }}/purchase_prices/logs?pagination_data=true
HTTP 200
