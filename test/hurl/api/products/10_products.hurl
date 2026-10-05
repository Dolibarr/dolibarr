# GET products
GET http://{{hostnport}}/api/index.php/products
HTTP 200

# GET sorted products
GET http://{{hostnport}}/api/index.php/products?sortfield=t.rowid&sortorder=ASC
HTTP 200

# GET sorted products descending
GET http://{{hostnport}}/api/index.php/products?sortfield=t.rowid&sortorder=DESC
HTTP 200

# GET with limit and page
GET http://{{hostnport}}/api/index.php/products?limit=100&page=1
HTTP 200

# GET with properties filter
GET http://{{hostnport}}/api/index.php/products?properties=id%2Cref%2Clabel
HTTP 200

# GET with pagination_data=false
GET http://{{hostnport}}/api/index.php/products?pagination_data=false
HTTP 200

# GET with pagination_data=true
GET http://{{hostnport}}/api/index.php/products?pagination_data=true
HTTP 200
[Asserts]
jsonpath "$.data" exists
jsonpath "$.pagination.total" >= 0
jsonpath "$.pagination.page" >= 0
jsonpath "$.pagination.page_count" >= 0
jsonpath "$.pagination.limit" == 100

# GET product with ID 0 - which should not exist
GET http://{{hostnport}}/api/index.php/products/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No Product with id<1 can exist"}}

# DELETE without ID
DELETE http://{{hostnport}}/api/index.php/products/
HTTP 405

# DELETE product with ID 0 - which should not exist
DELETE http://{{hostnport}}/api/index.php/products/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No Product with id<1 can exist"}}

# PUT without ID
PUT http://{{hostnport}}/api/index.php/products/
HTTP 405

# PUT product with ID 0 - which should not exist
PUT http://{{hostnport}}/api/index.php/products/0
HTTP 400
{"error":{"code":400,"message":"Bad Request: No Product with id<1 can exist"}}

# POST empty body
POST http://{{hostnport}}/api/index.php/products
{}
HTTP 400

# POST missing ref
POST http://{{hostnport}}/api/index.php/products
{ "label": "Missing ref test" }
HTTP 400

# POST missing label
POST http://{{hostnport}}/api/index.php/products
{ "ref": "MISSING-LABEL-TEST" }
HTTP 400


# GET products DOLAPIENTITY: 1
GET http://{{hostnport}}/api/index.php/products
DOLAPIENTITY: 1
HTTP 200

# GET products DOLAPIENTITY: 2 (unauthorized)
GET http://{{hostnport}}/api/index.php/products
DOLAPIENTITY: 2
HTTP 401
[Asserts]
jsonpath "$.error" exists
jsonpath "$.error.code" == 401
jsonpath "$.error.message" contains "Unauthorized"
jsonpath "$.error.message" contains "Token not valid"


# Capture a product ID for subsequent tests
GET http://{{hostnport}}/api/index.php/products?limit=1
HTTP 200
[Captures]
product_id: jsonpath "$[0]['id']"
product_ref: jsonpath "$[0]['ref']"

# GET single product by ID
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}
HTTP 200
[Asserts]
jsonpath "$.id" == {{ product_id }}
jsonpath "$.ref" exists
jsonpath "$.label" exists

# GET product by ref
GET http://{{hostnport}}/api/index.php/products/ref/{{ product_ref }}
HTTP 200
[Asserts]
jsonpath "$.ref" == "{{ product_ref }}"

# GET product categories
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/categories
HTTP 200

# GET product stock
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/stock
HTTP 200

# GET product subproducts
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/subproducts
HTTP 200

# GET product variants
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/variants
HTTP 200

# GET product purchase prices
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/purchase_prices
HTTP 200

# GET product contacts
GET http://{{hostnport}}/api/index.php/products/{{ product_id }}/contacts
HTTP 200

# GET all purchase prices
GET http://{{hostnport}}/api/index.php/products/purchase_prices
HTTP 200

# GET attributes
GET http://{{hostnport}}/api/index.php/products/attributes
HTTP 200

# GET product by non-existent ref
GET http://{{hostnport}}/api/index.php/products/ref/NONEXISTENT_REF_99999
HTTP 404

# GET product by non-existent ref_ext
GET http://{{hostnport}}/api/index.php/products/ref_ext/NONEXISTENT_REFEXT_99999
HTTP 404

# GET product by non-existent barcode
GET http://{{hostnport}}/api/index.php/products/barcode/0000000000
HTTP 404
